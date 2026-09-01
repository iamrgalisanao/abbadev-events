<?php

namespace App\Filament\Resources\Registrations\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('registration_number')
                    ->label('Reg #')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->description(fn ($record) => $record->email),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->toggleable(),
                TextColumn::make('event.title')
                    ->label('Event')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Registration')
                    ->badge(),
                TextColumn::make('payment.status')
                    ->label('Payment')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('lead_source')
                    ->label('Source')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->relationship('event', 'title')
                    ->label('Event'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
