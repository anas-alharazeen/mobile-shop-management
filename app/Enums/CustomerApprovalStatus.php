<?php

namespace App\Enums;

enum CustomerApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case NOT_REQUIRED = 'not_required';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'بانتظار الموافقة',
            self::APPROVED => 'موافق',
            self::REJECTED => 'مرفوض',
            self::NOT_REQUIRED => 'لا تحتاج موافقة',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::NOT_REQUIRED => 'gray',
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
