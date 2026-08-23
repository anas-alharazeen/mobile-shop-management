<?php

namespace App\Enums;

enum RepairSubStatus: string
{
    case WAITING_INSPECTION = 'waiting_inspection';
    case WAITING_CUSTOMER_APPROVAL = 'waiting_customer_approval';
    case WAITING_PART = 'waiting_part';
    case UNDER_TESTING = 'under_testing';
    case UNREPAIRABLE = 'unrepairable';
    case RETURNED_FOR_REPAIR = 'returned_for_repair';
    case NONE = 'none';

    public function label(): string
    {
        return match($this) {
            self::WAITING_INSPECTION => 'بانتظار الفحص',
            self::WAITING_CUSTOMER_APPROVAL => 'بانتظار موافقة العميل',
            self::WAITING_PART => 'بانتظار قطعة غيار',
            self::UNDER_TESTING => 'تحت الاختبار',
            self::UNREPAIRABLE => 'تعذر الإصلاح',
            self::RETURNED_FOR_REPAIR => 'مرتجع للصيانة',
            self::NONE => 'لا توجد حالة فرعية',
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
