<?php

namespace App\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->description(fn ($record) => $record->slug),
                TextColumn::make('type')->badge(),
                TextColumn::make('mode')->toggleable(),
                TextColumn::make('starts_at')
                    ->label('When')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                TextColumn::make('price_label')
                    ->label('Price')
                    ->badge()
                    ->color(fn ($record) => $record->isFree() ? 'success' : 'gray'),
                ToggleColumn::make('is_featured')->label('Featured'),
                ToggleColumn::make('is_active')->label('Active'),
                TextColumn::make('sort_order')->label('Order')->sortable()->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
