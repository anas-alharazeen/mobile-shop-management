<?php

namespace App\Enums;

enum TransactionType: string
{
    case SALE_PAYMENT = 'sale_payment';
    case REPAIR_PAYMENT = 'repair_payment';
    case REPAIR_PART_PURCHASE = 'repair_part_purchase';
    case PURCHASE_PAYMENT = 'purchase_payment';
    case SALES_REFUND = 'sales_refund';
    case PURCHASE_REFUND = 'purchase_refund';
    case EXCHANGE_DIFFERENCE = 'exchange_difference';
    case EXPENSE = 'expense';
    case MANUAL_DEPOSIT = 'manual_deposit';
    case MANUAL_WITHDRAWAL = 'manual_withdrawal';
    case BALANCE_ADJUSTMENT = 'balance_adjustment';
    case REVERSAL = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::SALE_PAYMENT => 'دفعة مبيعات',
            self::REPAIR_PAYMENT => 'دفعة صيانة',
            self::REPAIR_PART_PURCHASE => 'شراء قطعة صيانة خارجية',
            self::PURCHASE_PAYMENT => 'دفعة مشتريات',
            self::SALES_REFUND => 'استرداد مرتجع مبيعات',
            self::PURCHASE_REFUND => 'استرداد مرتجع مشتريات',
            self::EXCHANGE_DIFFERENCE => 'تسوية فرق استبدال',
            self::EXPENSE => 'مصروف',
            self::MANUAL_DEPOSIT => 'إيداع يدوي',
            self::MANUAL_WITHDRAWAL => 'سحب يدوي',
            self::BALANCE_ADJUSTMENT => 'تسوية رصيد',
            self::REVERSAL => 'عكس حركة',
        };
    }

    public static function labels(): array
    {
        return array_reduce(
            self::cases(),
            function (array $labels, self $type): array {
                $labels[$type->value] = $type->label();

                return $labels;
            },
            []
        );
    }
}
