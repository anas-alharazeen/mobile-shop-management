<?php

namespace App\Enums;

enum StockMovementType: string
{
    case OPENING = 'opening';
    case MANUAL_ADDITION = 'manual_addition';
    case MANUAL_DEDUCTION = 'manual_deduction';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case DAMAGED = 'damaged';
    case LOST = 'lost';
    case INVENTORY_SURPLUS = 'inventory_surplus';
    case INVENTORY_SHORTAGE = 'inventory_shortage';
    case PURCHASE = 'purchase';
    case PURCHASE_CANCELLATION = 'purchase_cancellation';
    case SALE = 'sale';
    case SALE_CANCELLATION = 'sale_cancellation';
    case REPAIR_PART = 'repair_part';
    case REPAIR_PART_REVERSAL = 'repair_part_reversal';
    case SALES_RETURN = 'sales_return';
    case PURCHASE_RETURN = 'purchase_return';
    case SALES_RETURN_REVERSAL = 'sales_return_reversal';
    case PURCHASE_RETURN_REVERSAL = 'purchase_return_reversal';

    public function label(): string
    {
        return match ($this) {
            self::OPENING => 'رصيد افتتاحي',
            self::MANUAL_ADDITION => 'إضافة يدوية',
            self::MANUAL_DEDUCTION => 'خصم يدوي',
            self::TRANSFER_IN => 'استلام من مخزن',
            self::TRANSFER_OUT => 'تحويل إلى مخزن',
            self::DAMAGED => 'تالف',
            self::LOST => 'مفقود',
            self::INVENTORY_SURPLUS => 'زيادة جرد',
            self::INVENTORY_SHORTAGE => 'عجز جرد',
            self::PURCHASE => 'شراء',
            self::PURCHASE_CANCELLATION => 'إلغاء شراء',
            self::SALE => 'بيع',
            self::SALE_CANCELLATION => 'إلغاء بيع',
            self::REPAIR_PART => 'استخدام قطعة في الصيانة',
            self::REPAIR_PART_REVERSAL => 'إعادة قطعة صيانة',
            self::SALES_RETURN => 'مرتجع مبيعات',
            self::PURCHASE_RETURN => 'مرتجع مشتريات',
            self::SALES_RETURN_REVERSAL => 'عكس مرتجع مبيعات',
            self::PURCHASE_RETURN_REVERSAL => 'عكس مرتجع مشتريات',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPENING => 'blue',
            self::MANUAL_ADDITION,
            self::TRANSFER_IN,
            self::INVENTORY_SURPLUS,
            self::PURCHASE,
            self::SALE_CANCELLATION,
            self::REPAIR_PART_REVERSAL,
            self::SALES_RETURN,
            self::PURCHASE_RETURN_REVERSAL => 'green',
            self::MANUAL_DEDUCTION,
            self::TRANSFER_OUT => 'orange',
            self::DAMAGED,
            self::LOST,
            self::INVENTORY_SHORTAGE,
            self::PURCHASE_CANCELLATION,
            self::SALE,
            self::REPAIR_PART,
            self::PURCHASE_RETURN,
            self::SALES_RETURN_REVERSAL => 'red',
        };
    }

    public function isAddition(): bool
    {
        return in_array($this, [
            self::OPENING,
            self::MANUAL_ADDITION,
            self::TRANSFER_IN,
            self::INVENTORY_SURPLUS,
            self::PURCHASE,
            self::SALE_CANCELLATION,
            self::REPAIR_PART_REVERSAL,
            self::SALES_RETURN,
            self::PURCHASE_RETURN_REVERSAL,
        ], true);
    }

    public function isDeduction(): bool
    {
        return in_array($this, [
            self::MANUAL_DEDUCTION,
            self::TRANSFER_OUT,
            self::DAMAGED,
            self::LOST,
            self::INVENTORY_SHORTAGE,
            self::PURCHASE_CANCELLATION,
            self::SALE,
            self::REPAIR_PART,
            self::PURCHASE_RETURN,
            self::SALES_RETURN_REVERSAL,
        ], true);
    }

    public static function labels(): array
    {
        return array_reduce(self::cases(), function (array $labels, self $case): array {
            $labels[$case->value] = $case->label();

            return $labels;
        }, []);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function additionTypes(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case): bool => $case->isAddition()));
    }

    public static function deductionTypes(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case): bool => $case->isDeduction()));
    }
}
