<?php

namespace App\Enums;

enum AccountType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case BANKING_APP = 'banking_app';

    public function label(): string
    {
        return match($this) {
            self::CASH => 'صندوق نقدي',
            self::BANK => 'حساب بنكي',
            self::BANKING_APP => 'تطبيق بنكي',
        };
    }

    public static function labels(): array
    {
        return array_reduce(self::cases(), function ($carry, $case) {
            $carry[$case->value] = $case->label();
            return $carry;
        }, []);
    }
}
