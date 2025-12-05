<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'client_id',
        'table_booking_id',
        'quantity',
        'payment_method',
        'sold_at',
        'total',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'quantity' => 'integer',
        'total' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
