<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $primaryKey = 'IDOrder';
    
    protected $fillable = [
        'IDClient',
        'IDTable',
        'IDWarehouses',
        'Tips',
        'Discount',
        'On_loan',
        'Total',
        'UserId',
        'Comment',
        'Status',
        'PaymentMethod' 
    ];

    protected $casts = [
        'Tips' => 'decimal:2',
        'Discount' => 'decimal:2',
        'On_loan' => 'decimal:2',
        'Total' => 'decimal:2',
    ];

    // Связь с позициями заказа (OrderItemProduct)
    public function orderItems()
    {
        return $this->hasMany(OrderItemProduct::class, 'IDOrder');
    }

    public function hookahItems()
    {
        return $this->hasMany(OrderItemHookah::class, 'IDOrder');
    }

    public function recipeItems()
    {   
        return $this->hasMany(OrderItemRecipe::class, 'IDOrder');
    }

    // Связь с клиентом (предполагаем, что модель Client существует)
    public function client()
    {
        return $this->belongsTo(Client::class, 'IDClient');
    }

    // Связь со столиком (предполагаем, что модель Table существует)
    public function table()
    {
        return $this->belongsTo(Table::class, 'IDTable');
    }

    // Связь со складом (предполагаем, что модель Warehouse существует)
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'IDWarehouses');
    }

    // Связь с пользователем (User)
    public function user()
    {
        return $this->belongsTo(User::class, 'UserId');
    }

    // Вычисляемое поле для общей суммы позиций
    public function getItemsTotalAttribute()
    {
        return $this->orderItems->sum(function($item) {
            return $item->Quantity * $item->UnitPrice;
        });
    }
    public function getStatusText()
    {
        $statuses = [
            'new' => 'Новый',
            'in_progress' => 'В работе',
            'completed' => 'Завершен',
            'cancelled' => 'Отменен'
        ];
        
        return $statuses[$this->Status] ?? $this->Status;
    }
    
    public function recalculateTotal()
    {
        // Загружаем связи, если они еще не загружены
        if (!$this->relationLoaded('orderItems')) {
            $this->load('orderItems.product');
        }
        if (!$this->relationLoaded('hookahItems')) {
            $this->load('hookahItems.hookah');
        }
        if (!$this->relationLoaded('recipeItems')) {
            $this->load('recipeItems.recipe');
        }
        
        // Сумма товаров
        $productsTotal = $this->orderItems->sum(function($item) {
            return $item->Quantity * $item->UnitPrice;
        });
        
        // Сумма кальянов
        $hookahsTotal = $this->hookahItems->sum(function($item) {
            return $item->hookah->price ?? 0;
        });
        
        // Сумма рецептов
        $recipesTotal = $this->recipeItems->sum(function($item) {
            return $item->Quantity * $item->UnitPrice;
        });
        
        // Общая сумма позиций
        $itemsTotal = $productsTotal + $hookahsTotal + $recipesTotal;
        
        // Применяем скидку и добавляем чаевые
        $finalTotal = $itemsTotal - $this->Discount + $this->Tips;
        
        // Обновляем только если сумма изменилась
        if ($this->Total != $finalTotal) {
            $this->update(['Total' => $finalTotal]);
        }
        
        return $finalTotal;
    }
}