<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'purchase_invoice_id',
        'supplier_id',
        'return_date',
        'status',
        'return_type',
        'total_amount',
        'amount_used_for_debt',
        'amount_refunded',
        'financial_account_id',
        'refund_method',
        'reason',
        'notes',
        'approved_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'return_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'amount_used_for_debt' => 'decimal:2',
        'amount_refunded' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function account()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('return_date', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $number = intval(substr($last->return_number, -6)) + 1;
        } else {
            $number = 1;
        }

        return 'PR-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
