<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getDefaultActiveTab(): string
    {
        return 'for_verification';
    }

    public function getTabs(): array
    {
        $count = fn (PaymentStatus $status) => Payment::where('status', $status->value)->count();
        $tab = fn (PaymentStatus $status) => Tab::make()
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status->value))
            ->badge($count($status));

        return [
            'for_verification' => $tab(PaymentStatus::ForVerification)->label('For verification'),
            'amount_mismatch' => $tab(PaymentStatus::AmountMismatch)->label('Amount mismatch'),
            'duplicate' => $tab(PaymentStatus::Duplicate)->label('Possible duplicate'),
            'verified' => $tab(PaymentStatus::Verified)->label('Verified'),
            'rejected' => $tab(PaymentStatus::Rejected)->label('Rejected'),
            'all' => Tab::make()->label('All'),
        ];
    }
}
