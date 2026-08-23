<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseInvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;
protected $with = ['supplier'];
    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'supplier_invoice_number',
        'purchase_date',
        'status',
        'subtotal',
        'discount_amount',
        'shipping_cost',
        'additional_expenses',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'payment_method',
        'payment_reference',
        'due_date',
        'attachment_path',
        'notes',
        'approved_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => PurchaseInvoiceStatus::class,
        'payment_status' => PaymentStatus::class,
        'purchase_date' => 'date',
        'due_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'additional_expenses' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function getCanBeEditedAttribute()
    {
        return $this->status === PurchaseInvoiceStatus::DRAFT;
    }

    public function getCanBeApprovedAttribute()
    {
        return $this->status === PurchaseInvoiceStatus::DRAFT
            && $this->items()->count() > 0;
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return $this->status === PurchaseInvoiceStatus::APPROVED
            && (float) $this->paid_amount <= 0;
    }

    public function getCanAddPaymentAttribute()
    {
        return $this->status === PurchaseInvoiceStatus::APPROVED
            && $this->payment_status !== PaymentStatus::PAID;
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($nested) use ($search) {
                $nested->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where(function ($supplierFields) use ($search) {
                            $supplierFields->where('name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                    });
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

    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('purchase_date', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $number = intval(substr($last->invoice_number, -6)) + 1;
        } else {
            $number = 1;
        }

        return 'PUR-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
