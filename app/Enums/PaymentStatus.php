<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case ForVerification = 'for_verification';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Duplicate = 'duplicate';
    case AmountMismatch = 'amount_mismatch';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::ForVerification => 'For verification',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Duplicate => 'Possible duplicate',
            self::AmountMismatch => 'Amount mismatch',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'gray',
            self::ForVerification => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
            self::Duplicate => 'warning',
            self::AmountMismatch => 'warning',
        };
    }
}
