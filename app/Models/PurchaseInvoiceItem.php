<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'unit_purchase_price',
        'line_discount',
        'line_subtotal',
        'allocated_expenses',
        'landed_unit_cost',
        'landed_line_cost',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_purchase_price' => 'decimal:2',
        'line_discount' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'allocated_expenses' => 'decimal:2',
        'landed_unit_cost' => 'decimal:2',
        'landed_line_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(
            PurchaseInvoice::class,
            'purchase_invoice_id'
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
}
