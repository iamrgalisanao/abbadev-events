<?php

namespace App\Filament\Certificates;

use App\Enums\CertificateStatus;
use App\Filament\Pages\CertificateGenerator;
use App\Models\Certificate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * The certificate admin actions, defined once and reused on both the table rows
 * and the certificate view page (Filament v5 unifies these actions).
 */
class CertificateActions
{
    /**
     * Revoking leaves the record in place: the QR on the printed copy keeps
     * resolving, and the verification page now reports it as revoked with the
     * reason. Deleting instead would leave a scanned QR with nothing to say.
     */
    public static function revoke(): Action
    {
        return Action::make('revoke')
            ->label('Revoke')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (Certificate $record) => $record->isActive())
            ->modalHeading('Revoke this certificate')
            ->modalDescription('The printed QR keeps working, but the verification page will report the credential as revoked and show the reason below.')
            ->schema([
                Textarea::make('revocation_reason')
                    ->label('Reason')
                    ->helperText('Shown publicly on the verification page.')
                    ->rows(2)
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (Certificate $record, array $data): void {
                $record->update([
                    'status' => CertificateStatus::Revoked,
                    'revocation_reason' => $data['revocation_reason'],
                ]);

                Notification::make()
                    ->title('Certificate revoked')
                    ->body('Anyone scanning the QR will now see it reported as revoked.')
                    ->success()
                    ->send();
            });
    }

    public static function reinstate(): Action
    {
        return Action::make('reinstate')
            ->label('Reinstate')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('success')
            ->visible(fn (Certificate $record) => ! $record->isActive())
            ->requiresConfirmation()
            ->modalHeading('Reinstate this certificate')
            ->modalDescription('The verification page will report the credential as valid again and the revocation reason will be cleared.')
            ->action(function (Certificate $record): void {
                $record->update([
                    'status' => CertificateStatus::Active,
                    'revocation_reason' => null,
                ]);

                Notification::make()->title('Certificate reinstated')->success()->send();
            });
    }

    public static function verificationPage(): Action
    {
        return Action::make('verificationPage')
            ->label('Verification page')
            ->icon(Heroicon::OutlinedQrCode)
            ->color('gray')
            ->url(fn (Certificate $record) => $record->verificationUrl())
            ->openUrlInNewTab();
    }

    public static function print(): Action
    {
        return Action::make('print')
            ->label('Print / Save as PDF')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('gray')
            ->url(fn (Certificate $record) => $record->downloadUrl())
            ->openUrlInNewTab();
    }

    public static function amend(): Action
    {
        return Action::make('amend')
            ->label('Amend in generator')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->url(fn (Certificate $record) => CertificateGenerator::getUrl(['certificate' => $record->getKey()]));
    }

    /**
     * Deleting breaks every QR already printed for this credential, so it is
     * for cleaning up test records rather than withdrawing a real one.
     */
    public static function delete(): DeleteAction
    {
        return DeleteAction::make()
            ->modalHeading('Delete this certificate')
            ->modalDescription('Any QR already printed for this credential will stop resolving and the verification page will report it as not found. To withdraw a certificate that has been handed out, revoke it instead.');
    }
}
