<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case BANKING_APP = 'banking_app';
    case EXCHANGE_CREDIT = 'exchange_credit';

    public function label(): string
    {
        return match($this) {
            self::CASH => 'كاش',
            self::BANK_TRANSFER => 'تحويل بنكي',
            self::BANKING_APP => 'تطبيق بنكي',
            self::EXCHANGE_CREDIT => 'رصيد استبدال',
        };
    }


    public static function selectableLabels(): array
    {
        return [
            self::CASH->value => self::CASH->label(),
            self::BANK_TRANSFER->value => self::BANK_TRANSFER->label(),
            self::BANKING_APP->value => self::BANKING_APP->label(),
        ];
    }

    public static function labels(): array
    {
        return array_reduce(self::cases(), function ($carry, $case) {
            $carry[$case->value] = $case->label();
            return $carry;
        }, []);
    }
}
