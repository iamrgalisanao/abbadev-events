<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Models\Registration;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class RegistrationsTable
{
    /**
     * Remove a registration's uploaded receipt files from disk. Deleting the
     * registration row cascades to its payments (FK), but never touches the
     * filesystem, so we clean those up first.
     */
    protected static function deleteReceipts(Registration $registration): void
    {
        foreach ($registration->payments as $payment) {
            if ($payment->receipt_path) {
                Storage::disk('local')->delete($payment->receipt_path);
            }
        }
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // Keep the tab badge counts live without a manual refresh.
            ->poll('10s')
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
                DeleteAction::make()
                    ->modalHeading('Delete this registration')
                    ->modalDescription('This permanently deletes the registration, its payment, and any uploaded receipt. This cannot be undone.')
                    ->before(fn (Registration $record) => self::deleteReceipts($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('This permanently deletes the selected registrations, their payments, and any uploaded receipts.')
                        ->before(function ($records): void {
                            foreach ($records as $record) {
                                self::deleteReceipts($record);
                            }
                        }),
                ]),
            ]);
    }
}
