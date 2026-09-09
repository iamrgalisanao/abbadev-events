<?php

namespace App\Filament\Resources\Certificates\Tables;

use App\Enums\CertificateStatus;
use App\Filament\Certificates\CertificateActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable()
                    ->description(fn ($record) => $record->credential_id)
                    ->wrap(),
                TextColumn::make('activity_title')
                    ->label('Session')
                    ->searchable()
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('issued_on')
                    ->label('Session date')
                    ->date('M j, Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->description(fn ($record) => $record->revocation_reason)
                    ->wrap(),
                TextColumn::make('issuer.name')
                    ->label('Issued by')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Issued')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(CertificateStatus::class)
                    ->label('Status'),
            ])
            ->recordActions([
                ViewAction::make(),
                CertificateActions::revoke(),
                CertificateActions::reinstate(),
                ActionGroup::make([
                    CertificateActions::verificationPage(),
                    CertificateActions::print(),
                    CertificateActions::amend(),
                    CertificateActions::delete(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('Any QR already printed for the selected credentials will stop resolving. To withdraw certificates that have been handed out, revoke them instead.'),
                ]),
            ]);
    }
}
