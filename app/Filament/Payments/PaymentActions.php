<?php

namespace App\Filament\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * The four admin verification actions, defined once and reused on both the
 * table rows and the payment view page (Filament v5 unifies these actions).
 */
class PaymentActions
{
    public static function confirm(): Action
    {
        return Action::make('confirm')
            ->label('Confirm Payment & Send Email')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Payment $record) => $record->status !== PaymentStatus::Verified)
            ->requiresConfirmation()
            ->modalHeading('Confirm payment & send email')
            ->modalDescription('Only do this after the transaction is confirmed in the GCash Business Portal. This confirms the registration and emails the registrant.')
            ->schema([
                Textarea::make('notes')->label('Verification notes')->rows(2),
            ])
            ->action(function (Payment $record, array $data): void {
                app(PaymentService::class)->confirm($record, auth()->user(), $data['notes'] ?? null);

                Notification::make()
                    ->title('Payment verified, registration confirmed, email sent')
                    ->success()
                    ->send();
            });
    }

    public static function reject(): Action
    {
        return self::statusAction('reject', 'Reject', PaymentStatus::Rejected, 'danger', Heroicon::OutlinedXCircle);
    }

    public static function markDuplicate(): Action
    {
        return self::statusAction('duplicate', 'Mark Duplicate', PaymentStatus::Duplicate, 'warning', Heroicon::OutlinedDocumentDuplicate);
    }

    public static function markMismatch(): Action
    {
        return self::statusAction('mismatch', 'Amount Mismatch', PaymentStatus::AmountMismatch, 'warning', Heroicon::OutlinedScale);
    }

    protected static function statusAction(string $name, string $label, PaymentStatus $status, string $color, Heroicon $icon): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (Payment $record) => $record->status !== PaymentStatus::Verified)
            ->requiresConfirmation()
            ->modalHeading($label)
            ->schema([
                Textarea::make('notes')->label('Notes')->rows(2),
            ])
            ->action(function (Payment $record, array $data) use ($status, $label): void {
                app(PaymentService::class)->setStatus($record, $status, auth()->user(), $data['notes'] ?? null);

                Notification::make()->title("Marked as {$label}")->success()->send();
            });
    }
}
