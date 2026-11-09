<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case JazzCash = 'jazzcash';
    case EasyPaisa = 'easypaisa';
    case Cheque = 'cheque';
    case Bank = 'bank';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::JazzCash => 'JazzCash',
            self::EasyPaisa => 'EasyPaisa',
            self::Cheque => 'Cheque',
            self::Bank, self::BankTransfer => 'Bank Transfer',
            self::Other => 'Other',
        };
    }

    public static function fromPaymentMethod(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
