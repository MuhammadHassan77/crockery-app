<?php

namespace App\Enums;

enum PaymentHistoryType: string
{
    case Legacy = 'legacy';
    case Receipt = 'receipt';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Legacy => 'Legacy',
            self::Receipt => 'Receipt',
            self::Credit => 'Customer Credit',
        };
    }
}
