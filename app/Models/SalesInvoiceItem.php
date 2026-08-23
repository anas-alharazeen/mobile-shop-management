<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'product_id',
        'warehouse_id',
        'product_name',
        'product_code',
        'quantity',
        'unit_cost',
        'unit_selling_price',
        'line_discount',
        'allocated_invoice_discount',
        'line_subtotal',
        'line_total',
        'line_cost',
        'line_profit',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_selling_price' => 'decimal:2',
        'line_discount' => 'decimal:2',
        'allocated_invoice_discount' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_total' => 'decimal:2',
        'line_cost' => 'decimal:2',
        'line_profit' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(
            SalesInvoice::class,
            'sales_invoice_id'
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function returnItems()
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}
