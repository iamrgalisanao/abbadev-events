<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Payments\PaymentActions;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewReceipt')
                ->label('View Receipt')
                ->icon(Heroicon::OutlinedPhoto)
                ->color('gray')
                ->url(fn (Payment $record) => route('admin.receipts', $record))
                ->openUrlInNewTab()
                ->visible(fn (Payment $record) => (bool) $record->receipt_path),
            PaymentActions::confirm(),
            PaymentActions::reject(),
            PaymentActions::markDuplicate(),
            PaymentActions::markMismatch(),
            PaymentActions::delete(),
        ];
    }
}
