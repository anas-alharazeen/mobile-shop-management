<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesExchange extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_number',
        'sales_return_id',
        'new_sales_invoice_id',
        'return_value',
        'exchange_credit',
        'new_items_value',
        'price_difference',
        'settlement_type',
        'financial_account_id',
        'notes',
        'exchanged_at',
    ];

    protected $casts = [
        'return_value' => 'decimal:2',
        'exchange_credit' => 'decimal:2',
        'new_items_value' => 'decimal:2',
        'price_difference' => 'decimal:2',
        'exchanged_at' => 'datetime',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function newInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'new_sales_invoice_id');
    }

    public function account()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('exchanged_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $number = intval(substr($last->exchange_number, -6)) + 1;
        } else {
            $number = 1;
        }

        return 'EXC-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
