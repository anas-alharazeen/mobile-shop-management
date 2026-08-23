<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\SalesInvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory;
protected $with = ['customer'];

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'sale_date',
        'due_date',
        'status',
        'subtotal',
        'items_discount',
        'invoice_discount',
        'total_amount',
        'total_cost',
        'gross_profit',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'notes',
        'approved_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => SalesInvoiceStatus::class,
        'payment_status' => PaymentStatus::class,
        'sale_date' => 'date',
        'due_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'items_discount' => 'decimal:2',
        'invoice_discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalesPayment::class);
    }

    public function getCanBeEditedAttribute()
    {
        return $this->status === SalesInvoiceStatus::DRAFT;
    }

    public function getCanBeApprovedAttribute()
    {
        return $this->status === SalesInvoiceStatus::DRAFT
            && $this->items()->count() > 0;
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return $this->status === SalesInvoiceStatus::APPROVED
            && (float) $this->paid_amount <= 0;
    }

    public function getCanAddPaymentAttribute()
    {
        return $this->status === SalesInvoiceStatus::APPROVED
            && $this->payment_status !== PaymentStatus::PAID;
    }

    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('sale_date', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $number = intval(substr($last->invoice_number, -6)) + 1;
        } else {
            $number = 1;
        }

        return 'SAL-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($nested) use ($search) {
                $nested->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    public function scopeStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopePaymentStatus($query, $status)
    {
        if ($status) {
            return $query->where('payment_status', $status);
        }
        return $query;
    }
}
