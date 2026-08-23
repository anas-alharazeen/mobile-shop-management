<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_number',
        'expense_category_id',
        'financial_account_id',
        'amount',
        'expense_date',
        'beneficiary',
        'description',
        'attachment_path',
        'notes',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => ExpenseStatus::class,
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function account()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function getCanBeCancelledAttribute()
    {
        return $this->status === ExpenseStatus::POSTED;
    }

    public function getStatusLabelAttribute()
    {
        return $this->status?->label() ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        return $this->status?->color() ?? 'gray';
    }

    public static function generateNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('expense_date', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $number = intval(substr($last->expense_number, -6)) + 1;
        } else {
            $number = 1;
        }

        return 'EXP-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($nested) use ($search) {
                $nested->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('beneficiary', 'like', "%{$search}%");
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
}
