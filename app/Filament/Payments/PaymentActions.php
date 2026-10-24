<?php

namespace App\Filament\Payments;

use App\Enums\EmailStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

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
                $payment = app(PaymentService::class)->confirm($record, auth()->user(), $data['notes'] ?? null);

                $payment->email_status === EmailStatus::Sent
                    ? Notification::make()->title('Payment verified, registration confirmed, email sent')->success()->send()
                    : Notification::make()
                        ->title('Payment verified and registration confirmed, but the email failed')
                        ->body('Use "Resend email" on this payment once the n8n webhook is working.')
                        ->warning()
                        ->send();
            });
    }

    /**
     * Confirm every selected payment still awaiting verification. The DB work
     * runs inline (fast); the n8n emails go out after the response so the admin
     * is never blocked on the webhook, and no queue worker is required.
     */
    public static function bulkConfirm(): BulkAction
    {
        return BulkAction::make('bulkConfirm')
            ->label('Confirm selected & send emails')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirm selected payments')
            ->modalDescription('Only do this after every selected transaction is confirmed in the GCash Business Portal. Payments that are not "For verification" (amount mismatch, duplicate, rejected, already verified) are skipped.')
            ->schema([
                Textarea::make('notes')->label('Verification notes')->rows(2),
            ])
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data): void {
                $result = app(PaymentService::class)->confirmMany($records, auth()->user(), $data['notes'] ?? null);
                $ids = collect($result['confirmed'])->pluck('id')->all();

                if ($ids) {
                    self::sendEmailsAfterResponse($ids);
                }

                Notification::make()
                    ->title(count($ids) . ' payment(s) confirmed' . ($result['skipped'] ? ", {$result['skipped']} skipped" : ''))
                    ->body($ids ? 'Confirmation emails are being sent. Check the Email status column in a moment.' : 'Nothing was confirmed: only "For verification" payments qualify.')
                    ->{$ids ? 'success' : 'warning'}()
                    ->send();
            });
    }

    /**
     * Resend the confirmation email for a verified payment whose email failed
     * or never went out (e.g. while the n8n webhook was misconfigured).
     */
    public static function resendEmail(): Action
    {
        return Action::make('resendEmail')
            ->label('Resend email')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->visible(fn (Payment $record) => $record->status === PaymentStatus::Verified
                && $record->email_status !== EmailStatus::Sent)
            ->requiresConfirmation()
            ->action(function (Payment $record): void {
                $result = app(PaymentService::class)->sendConfirmationEmails([$record]);

                $result['sent']
                    ? Notification::make()->title('Confirmation email sent')->success()->send()
                    : Notification::make()->title('Email failed again')->body('Check the n8n webhook and storage/logs/laravel.log.')->danger()->send();
            });
    }

    public static function bulkResendEmail(): BulkAction
    {
        return BulkAction::make('bulkResendEmail')
            ->label('Resend failed emails')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Resends the confirmation email for selected verified payments whose email has not been sent. Anything already sent is skipped.')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $ids = $records->filter(fn (Payment $p) => $p->status === PaymentStatus::Verified
                    && $p->email_status !== EmailStatus::Sent)->pluck('id')->all();

                if ($ids) {
                    self::sendEmailsAfterResponse($ids);
                }

                Notification::make()
                    ->title(count($ids) . ' email(s) queued to send')
                    ->body(count($ids) < $records->count() ? 'The rest were skipped (not verified, or already sent).' : null)
                    ->{$ids ? 'success' : 'warning'}()
                    ->send();
            });
    }

    /**
     * @param  list<int>  $ids
     */
    protected static function sendEmailsAfterResponse(array $ids): void
    {
        app()->terminating(function () use ($ids): void {
            set_time_limit(0);

            app(PaymentService::class)->sendConfirmationEmails(
                Payment::with('registration.event')->findMany($ids)
            );
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

    /**
     * Permanently delete a payment, its receipt file, and its registration —
     * for cleaning up test/spam records.
     */
    public static function delete(): DeleteAction
    {
        return DeleteAction::make()
            ->modalHeading('Delete this record')
            ->modalDescription('This permanently deletes the payment, its uploaded receipt, and the registration. This cannot be undone.')
            ->before(function (Payment $record): void {
                if ($record->receipt_path) {
                    Storage::disk('local')->delete($record->receipt_path);
                }
            })
            ->after(function (Payment $record): void {
                $record->registration?->delete();
            });
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
