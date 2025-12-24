<?php

namespace App\Filament\Instructor\Resources\QuizResource\RelationManagers;

use App\Enums\QuestionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $recordTitleAttribute = 'question_text';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options(collect(QuestionType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                    ->required()
                    ->default(QuestionType::SingleChoice->value),

                Forms\Components\TextInput::make('points')
                    ->numeric()
                    ->default(1)
                    ->minValue(1),

                Forms\Components\Textarea::make('question_text')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('explanation')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Forms\Components\Repeater::make('answers')
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('answer_text')
                            ->required(),

                        Forms\Components\Toggle::make('is_correct')
                            ->default(false),

                        Forms\Components\Hidden::make('sort_order')
                            ->default(0),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->defaultItems(4)
                    ->columnSpanFull()
                    ->reorderable()
                    ->orderColumn('sort_order'),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('question_text')
                    ->limit(60)
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (QuestionType $state): string => $state->color()),

                Tables\Columns\TextColumn::make('points')
                    ->sortable(),

                Tables\Columns\TextColumn::make('answers_count')
                    ->counts('answers')
                    ->label('Answers'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
