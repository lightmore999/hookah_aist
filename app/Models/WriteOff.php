<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WriteOff extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity', // Теперь decimal
        'write_off_date',
        'operation_type',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'write_off_date' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Получить форматированное количество
     */
    public function getFormattedQuantityAttribute()
    {
        $product = $this->product;
        $unit = $product->unit ?? 'шт';
        
        // Показываем в формате: X (Y уп. + Z ед.)
        $packaging = $product->packaging ?? 1;
        $wholePackages = floor($this->quantity / $packaging);
        $opened = fmod($this->quantity, $packaging);
        
        $result = number_format($this->quantity, 3) . ' ' . $unit;
        
        if ($packaging > 1) {
            $result .= " ({$wholePackages} уп. + " . number_format($opened, 3) . " {$unit})";
        }
        
        return $result;
    }

    /**
     * Получить количество целых упаковок
     */
    public function getWholePackagesAttribute()
    {
        $product = $this->product;
        $packaging = $product->packaging ?? 1;
        return floor($this->quantity / $packaging);
    }

    /**
     * Получить количество из открытой упаковки
     */
    public function getOpenedQuantityAttribute()
    {
        $product = $this->product;
        $packaging = $product->packaging ?? 1;
        return fmod($this->quantity, $packaging);
    }
}