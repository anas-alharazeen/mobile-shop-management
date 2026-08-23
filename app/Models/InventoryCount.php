<?php

namespace App\Models;

use App\Enums\InventoryCountStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'warehouse_id',
        'category_id',
        'status',
        'count_date',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'status' => InventoryCountStatus::class,
        'count_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryCountItem::class);
    }

    public function scopeStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('reference_number', 'like', "%{$search}%");
        }
        return $query;
    }

    public function getProgressAttribute()
    {
        $total = $this->items()->count();
        if ($total === 0) return 0;

        $counted = $this->items()->whereNotNull('actual_quantity')->count();
        return round(($counted / $total) * 100);
    }

    public function getSummaryAttribute()
    {
        $items = $this->items;
        $total = $items->count();
        $counted = $items->whereNotNull('actual_quantity')->count();
        $differences = $items->where('difference', '!=', 0)->count();
        $surplus = $items->where('difference', '>', 0)->count();
        $shortage = $items->where('difference', '<', 0)->count();
        $surplusValue = $items->where('difference', '>', 0)->sum('difference_value');
        $shortageValue = abs($items->where('difference', '<', 0)->sum('difference_value'));

        return [
            'total' => $total,
            'counted' => $counted,
            'remaining' => $total - $counted,
            'differences' => $differences,
            'surplus' => $surplus,
            'shortage' => $shortage,
            'surplus_value' => $surplusValue,
            'shortage_value' => $shortageValue,
            'net_difference' => $surplusValue - $shortageValue,
        ];
    }
}
