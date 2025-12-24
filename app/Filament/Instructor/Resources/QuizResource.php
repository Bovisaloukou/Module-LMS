<?php

namespace App\Filament\Instructor\Resources;

use App\Filament\Instructor\Resources\QuizResource\Pages;
use App\Filament\Instructor\Resources\QuizResource\RelationManagers;
use App\Models\Course;
use App\Models\Quiz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('course', fn (Builder $q) => $q->where('instructor_id', auth()->id()));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quiz Information')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->options(fn () => Course::where('instructor_id', auth()->id())->pluck('title', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('lesson_id')
                            ->relationship('lesson', 'title')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('pass_percentage')
                            ->numeric()
                            ->default(70)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\TextInput::make('max_attempts')
                            ->numeric()
                            ->default(3)
                            ->helperText('0 = unlimited'),

                        Forms\Components\TextInput::make('time_limit_minutes')
                            ->numeric()
                            ->nullable()
                            ->suffix('min'),

                        Forms\Components\Toggle::make('is_published')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('course.title')
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attempts_count')
                    ->counts('attempts')
                    ->label('Attempts')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pass_percentage')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->options(fn () => Course::where('instructor_id', auth()->id())->pluck('title', 'id')),

                Tables\Filters\TernaryFilter::make('is_published'),
            ])
            ->actions([
                Tables\Actions\Action::make('togglePublish')
                    ->icon(fn (Quiz $record) => $record->is_published ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Quiz $record) => $record->is_published ? 'danger' : 'success')
                    ->label(fn (Quiz $record) => $record->is_published ? 'Unpublish' : 'Publish')
                    ->action(fn (Quiz $record) => $record->update(['is_published' => ! $record->is_published])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }
}
