<?php

namespace App\Filament\Admin\Resources;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Filament\Admin\Resources\CourseResource\Pages;
use App\Filament\Admin\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Courses';

    protected static ?int $navigationSort = 0;

    public static function getNavigationBadge(): ?string
    {
        return (string) Course::where('status', CourseStatus::PendingReview)->count() ?: null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course Information')
                    ->schema([
                        Forms\Components\Select::make('instructor_id')
                            ->relationship('instructor', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('subtitle')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('short_description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\Toggle::make('is_free')
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, bool $state) => $state ? $set('price', 0) : null),

                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->disabled(fn (Forms\Get $get) => $get('is_free')),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Select::make('level')
                            ->options(collect(CourseLevel::cases())->mapWithKeys(fn ($level) => [$level->value => $level->label()]))
                            ->default(CourseLevel::Beginner->value),

                        Forms\Components\Select::make('status')
                            ->options(collect(CourseStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]))
                            ->default(CourseStatus::Draft->value),

                        Forms\Components\TextInput::make('language')
                            ->default('en')
                            ->maxLength(10),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->default(0)
                            ->suffix('min'),
                    ])->columns(2),

                Forms\Components\Section::make('Course Content')
                    ->schema([
                        Forms\Components\TagsInput::make('requirements')
                            ->placeholder('Add a requirement'),

                        Forms\Components\TagsInput::make('what_you_learn')
                            ->placeholder('Add a learning outcome'),
                    ])->columns(2),

                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('thumbnail')
                            ->collection('thumbnail')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9'),

                        Forms\Components\SpatieMediaLibraryFileUpload::make('preview_video')
                            ->collection('preview_video')
                            ->acceptedFileTypes(['video/mp4', 'video/webm']),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->collection('thumbnail')
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('instructor.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (CourseStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->color(fn (CourseLevel $state): string => $state->color()),

                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('modules_count')
                    ->counts('modules')
                    ->label('Modules')
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Students')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(CourseStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),

                Tables\Filters\SelectFilter::make('level')
                    ->options(collect(CourseLevel::cases())->mapWithKeys(fn ($l) => [$l->value => $l->label()])),

                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('is_free'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('publish')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Course $record) => $record->status !== CourseStatus::Published)
                    ->action(fn (Course $record) => $record->publish()),

                Tables\Actions\Action::make('archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Course $record) => $record->status === CourseStatus::Published)
                    ->action(fn (Course $record) => $record->archive()),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ModulesRelationManager::class,
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
