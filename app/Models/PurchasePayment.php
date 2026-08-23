<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'amount',
        'financial_account_id',
        'payment_method',
        'bank_or_app_name',
        'transaction_reference',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_method' => PaymentMethod::class,
        'paid_at' => 'datetime',
    ];

    public function financialAccount()
    {
        return $this->belongsTo(
            FinancialAccount::class,
            'financial_account_id'
        );
    }

    /*
     * مهم:
     * اسم الدالة invoice لا يجعل Laravel يعرف تلقائياً
     * أن المفتاح هو purchase_invoice_id.
     */
    public function invoice()
    {
        return $this->belongsTo(
            PurchaseInvoice::class,
            'purchase_invoice_id'
        );
    }
}
