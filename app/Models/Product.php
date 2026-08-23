<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'barcode',
        'brand',
        'model',
        'purchase_price',
        'selling_price',
        'minimum_selling_price',
        'low_stock_threshold',
        'location',
        'image_path',
        'description',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'minimum_selling_price' => 'decimal:2',
    ];

    // ========== العلاقات ==========

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * علاقة بمخزون المبيعات (hasOne)
     */
    public function salesStock()
    {
        return $this->hasOne(ProductStock::class)
            ->whereHas('warehouse', function ($query) {
                $query->where('type', 'sales');
            });
    }

    /**
     * علاقة بمخزون الصيانة (hasOne)
     */
    public function maintenanceStock()
    {
        return $this->hasOne(ProductStock::class)
            ->whereHas('warehouse', function ($query) {
                $query->where('type', 'maintenance');
            });
    }

    // ========== الخصائص المحسوبة ==========

    /**
     * إجمالي الكمية في جميع المخازن
     */
    public function getTotalStockAttribute(): int
    {
        if ($this->relationLoaded('stocks')) {
            return (int) $this->stocks->sum('quantity');
        }

        return (int) $this->stocks()->sum('quantity');
    }

    /**
     * قيمة الربح = سعر البيع - سعر الشراء
     */
    public function getProfitAttribute()
    {
        return $this->selling_price - $this->purchase_price;
    }

    /**
     * نسبة الربح المئوية
     */
    public function getProfitMarginAttribute()
    {
        if ($this->purchase_price == 0) {
            return 0;
        }
        return round(($this->profit / $this->purchase_price) * 100, 2);
    }

    /**
     * حالة المخزون بناءً على الكمية والحد الأدنى
     * returns: ['label' => 'متوفر|منخفض|نافد', 'color' => 'green|orange|red']
     */
    public function getStockStatusAttribute()
    {
        $total = $this->total_stock;
        $threshold = $this->low_stock_threshold ?? 5;

        if ($total <= 0) {
            return ['label' => 'نافد', 'color' => 'red'];
        }

        if ($total <= $threshold) {
            return ['label' => 'منخفض', 'color' => 'orange'];
        }

        return ['label' => 'متوفر', 'color' => 'green'];
    }

    /**
     * قيمة المخزون الإجمالية بسعر الشراء
     */
    public function getInventoryValueAttribute()
    {
        return $this->purchase_price * $this->total_stock;
    }

    /**
     * جلب كمية المخزون في مخزن معين
     */
    public function getStockInWarehouse($warehouseId)
    {
        $stock = $this->stocks()->where('warehouse_id', $warehouseId)->first();
        return $stock ? $stock->quantity : 0;
    }

    // ========== النطاقات (Scopes) ==========

    /**
     * نطاق: المنتجات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق: البحث بالاسم، الكود، أو الباركود
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function ($nested) use ($search) {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * نطاق: التصفية حسب الفئة
     */
    public function scopeCategory($query, $categoryId)
    {
        if ($categoryId) {
            return $query->where('category_id', $categoryId);
        }
        return $query;
    }

    /**
     * نطاق: التصفية حسب حالة المخزون
     * ملاحظة: يتم التصفية في الـ Controller باستخدام الكوليكشن
     */
    public function scopeStockStatus($query, $status)
    {
        if ($status) {
            // التصفية تتم في الـ Controller
            return $query;
        }
        return $query;
    }

    /**
     * نطاق: المنتجات التي لديها رصيد في مخزن معين
     */
    public function scopeHasStockInWarehouse($query, $warehouseId)
    {
        if ($warehouseId) {
            return $query->whereHas('stocks', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }
        return $query;
    }

    /**
     * نطاق: المنتجات التي رصيدها أقل من الحد الأدنى
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('stocks', function ($q) {
            $q->selectRaw('SUM(quantity) as total')
                ->havingRaw('total <= products.low_stock_threshold');
        });
    }

    /**
     * نطاق: المنتجات النافدة (رصيد = 0)
     */
    public function scopeOutOfStock($query)
    {
        return $query->whereDoesntHave('stocks', function ($q) {
            $q->where('quantity', '>', 0);
        });
    }
}
