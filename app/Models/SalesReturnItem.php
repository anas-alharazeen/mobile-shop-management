<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_return_id',
        'sales_invoice_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_amount',
        'product_condition',
        'restock',
        'warehouse_id',
        'notes',
    ];

    protected $casts = [
        'restock' => 'boolean',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function return()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(SalesInvoiceItem::class, 'sales_invoice_item_id');
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
