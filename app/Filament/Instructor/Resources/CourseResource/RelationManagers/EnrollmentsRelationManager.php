<?php

namespace App\Filament\Instructor\Resources\CourseResource\RelationManagers;

use App\Enums\EnrollmentStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_paid')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (EnrollmentStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('enrolled_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('enrolled_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(EnrollmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
