<?php

namespace App\Models;

use App\Enums\CustomerApprovalStatus;
use App\Enums\PaymentStatus;
use App\Enums\RepairMode;
use App\Enums\RepairOrderStatus;
use App\Enums\RepairSubStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class RepairOrder extends Model
{
    use HasFactory, SoftDeletes;
protected $with = ['customer'];

    protected $fillable = [
        'order_number',
        'repair_mode',
        'customer_id',
        'customer_name',
        'customer_phone',
        'device_type',
        'brand',
        'model',
        'color',
        'problem_description',
        'device_condition',
        'received_accessories',
        'lock_code',
        'technician_name',
        'status',
        'sub_status',
        'inspection_result',
        'fault_cause',
        'repair_action',
        'customer_approval_status',
        'estimated_cost',
        'inspection_fee',
        'labor_cost',
        'parts_cost',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'received_at',
        'due_date',
        'expected_delivery_date',
        'completed_at',
        'delivered_at',
        'warranty_days',
        'warranty_expires_at',
        'customer_notes',
        'internal_notes',
        'cancellation_reason',
    ];

    protected $casts = [
        'repair_mode' => RepairMode::class,
        'status' => RepairOrderStatus::class,
        'sub_status' => RepairSubStatus::class,
        'customer_approval_status' => CustomerApprovalStatus::class,
        'payment_status' => PaymentStatus::class,
        'received_at' => 'datetime',
        'due_date' => 'date',
        'expected_delivery_date' => 'date',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'warranty_expires_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'inspection_fee' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    protected $hidden = ['lock_code'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function attachments()
    {
        return $this->hasMany(RepairAttachment::class);
    }

    public function parts()
    {
        return $this->hasMany(RepairPart::class);
    }

    public function committedParts()
    {
        return $this->hasMany(RepairPart::class)->where('is_committed', true);
    }

    public function payments()
    {
        return $this->hasMany(RepairPayment::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(RepairStatusHistory::class)->orderBy('created_at', 'asc');
    }

    public function getLockCodeDecryptedAttribute()
    {
        if (!$this->lock_code) {
            return null;
        }
        try {
            return Crypt::decryptString($this->lock_code);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setLockCodeAttribute($value)
    {
        if ($value) {
            $this->attributes['lock_code'] = Crypt::encryptString($value);
        } else {
            $this->attributes['lock_code'] = null;
        }
    }

    public function getProfitAttribute(): float
    {
        // رسوم الفحص وأجرة الصيانة إيراد خدمة، والتكلفة المباشرة المسجلة هي تكلفة القطع.
        return max(0, (float) $this->total_amount - (float) $this->parts_cost);
    }

    public function getPartsPriceTotalAttribute()
    {
        return $this->parts()->sum('total_price');
    }

    public function getPartsCostTotalAttribute()
    {
        return $this->parts()->sum('total_cost');
    }

    public function getIsOverdueAttribute()
    {
        if ($this->status === RepairOrderStatus::DELIVERED || $this->status === RepairOrderStatus::CANCELLED) {
            return false;
        }
        if (!$this->expected_delivery_date) {
            return false;
        }
        return $this->expected_delivery_date < now();
    }

    public function getCanBeEditedAttribute()
    {
        return in_array($this->status, [RepairOrderStatus::RECEIVED, RepairOrderStatus::IN_PROGRESS]);
    }

    public function getCanAddPartsAttribute()
    {
        return in_array($this->status, [RepairOrderStatus::RECEIVED, RepairOrderStatus::IN_PROGRESS]);
    }

    public function getCanBeCompletedAttribute()
    {
        return $this->status === RepairOrderStatus::IN_PROGRESS
            && $this->customer_approval_status !== CustomerApprovalStatus::PENDING;
    }

    public function getCanBeDeliveredAttribute()
    {
        return $this->status === RepairOrderStatus::READY;
    }

    public function getCanAddPaymentAttribute()
    {
        return $this->status !== RepairOrderStatus::CANCELLED
            && $this->payment_status !== PaymentStatus::PAID
            && (float) $this->remaining_amount > 0;
    }

    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('received_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $number = intval(substr($last->order_number, -6)) + 1;
        } else {
            $number = 1;
        }

        return 'REP-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($nested) use ($search) {
                $nested->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('device_type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
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

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'delivered')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('expected_delivery_date')
            ->where('expected_delivery_date', '<', now());
    }
}
