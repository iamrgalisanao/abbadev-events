<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Payments\PaymentActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // Re-render the list (table + tab badge counts) on an interval so the
            // "For verification / Verified / ..." counts stay correct after a
            // verify or delete without a manual page refresh. Verify/delete also
            // update the badges instantly, since the action re-renders the page.
            ->poll('10s')
            ->columns([
                TextColumn::make('registration.registration_number')
                    ->label('Reg #')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('registration.name')
                    ->label('Registrant')
                    ->searchable()
                    ->description(fn ($record) => $record->registration?->email),
                TextColumn::make('registration.event.title')
                    ->label('Event')
                    ->toggleable(),
                TextColumn::make('amount_submitted')
                    ->label('Amount')
                    ->money('PHP')
                    ->description(fn ($record) => $record->hasAmountMismatch()
                        ? 'Expected PHP ' . number_format((float) $record->expected_amount, 2)
                        : null)
                    ->color(fn ($record) => $record->hasAmountMismatch() ? 'warning' : null),
                TextColumn::make('reference_number')
                    ->label('GCash Ref')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->relationship('registration.event', 'title')
                    ->label('Event'),
            ])
            ->recordActions([
                ViewAction::make(),
                PaymentActions::confirm(),
                PaymentActions::delete(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('This permanently deletes the selected payments, their receipts, and registrations.')
                        ->before(function ($records): void {
                            foreach ($records as $record) {
                                if ($record->receipt_path) {
                                    Storage::disk('local')->delete($record->receipt_path);
                                }
                            }
                        })
                        ->after(function ($records): void {
                            foreach ($records as $record) {
                                $record->registration?->delete();
                            }
                        }),
                ]),
            ]);
    }
}
