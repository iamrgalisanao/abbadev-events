<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmailStatus: string implements HasLabel, HasColor
{
    case NotSent = 'not_sent';
    case Sent = 'sent';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NotSent => 'Not sent',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NotSent => 'gray',
            self::Sent => 'success',
            self::Failed => 'danger',
        };
    }
}
