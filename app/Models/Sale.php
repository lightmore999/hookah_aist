<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Stock;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'warehouse_id',
        'table_id',
        'total',
        'discount',
        'used_bonus_points', // добавляем только это поле
        'payment_method',
        'status',
        'comment',
        'sale_date',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'sale_date' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function hookahs()
    {
        return $this->belongsToMany(Hookah::class, 'sale_hookahs')
                    ->withTimestamps();
    }

    public function table()
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'new' => 'Новый',
            'in_progress' => 'В работе',
            'completed' => 'Завершен',
            'cancelled' => 'Отменен'
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }

    // Добавляем бонусную скидку как вычисляемое поле
    public function getBonusDiscountAttribute()
    {
        // 1 бонус = 1 рубль (или другая валюта)
        return $this->used_bonus_points;
    }

    // Обновляем FinalTotal чтобы учитывал бонусы
    public function getFinalTotalAttribute()
    {
        $total = $this->total - $this->discount - ($this->used_bonus_points ?? 0);
        return max(0, $total); // Не даем уйти в минус
    }

    public function getHookahsTotalAttribute()
    {
        return $this->hookahs->sum('price');
    }

    public function hasTable()
    {
        return !is_null($this->table_id);
    }

    // Методы для работы с бонусами (УПРОЩЕННЫЕ)
    public function canUseBonuses()
    {
        return $this->client_id && $this->client && $this->client->bonus_points > 0;
    }

    public function getMaxUsableBonuses()
    {
        if (!$this->canUseBonuses()) {
            return 0;
        }

        // Получаем карту клиента для правил списания
        $bonusCard = $this->client->bonusCard;
        $maxPercent = $bonusCard ? $bonusCard->MaxSpendPercent : 50; // 50% по умолчанию
        
        // Можно использовать не более X% суммы заказа
        $maxBonusesByTotal = floor($this->total * ($maxPercent / 100));
        
        // Но не больше, чем есть у клиента
        return min($this->client->bonus_points, $maxBonusesByTotal);
    }

    // Метод для применения бонусов
    public function applyBonuses($pointsToUse)
    {
        if (!$this->client_id || $this->status === 'completed') {
            return [
                'success' => false,
                'message' => 'Нельзя применить бонусы'
            ];
        }

        $client = $this->client;
        $maxUsable = $this->getMaxUsableBonuses();
        
        if ($pointsToUse > $maxUsable) {
            return [
                'success' => false,
                'message' => "Можно использовать не более {$maxUsable} бонусов"
            ];
        }

        // Получаем текущий баланс до списания
        $oldBalance = $client->bonus_points;
        
        // Списываем бонусы у клиента
        $client->bonus_points -= $pointsToUse;
        $client->save();

        // Сохраняем в продажу
        $this->used_bonus_points = $pointsToUse;
        $this->save();

        // СОХРАНЯЕМ В ИСТОРИЮ СПИСАНИЕ БОНУСОВ
        \App\Models\BonusHistory::create([
            'client_id' => $this->client_id,
            'amount' => $pointsToUse,
            'operation_type' => 'debit',
            'balance_after' => $client->bonus_points, // баланс после списания
            'reason' => 'Списание бонусов при оплате продажи #' . $this->id,
            'sale_id' => $this->id,
        ]);

        return [
            'success' => true,
            'message' => "Использовано {$pointsToUse} бонусов",
            'bonus_discount' => $pointsToUse,
        ];
    }

    // Метод для отмены бонусов
    public function cancelBonuses()
    {
        if ($this->status === 'completed' || $this->used_bonus_points == 0) {
            return [
                'success' => false,
                'message' => 'Бонусы не могут быть возвращены'
            ];
        }

        // Возвращаем бонусы клиенту
        $client = $this->client;
        $client->bonus_points += $this->used_bonus_points;
        $client->save();

        $this->used_bonus_points = 0;
        $this->save();

        return [
            'success' => true,
            'message' => 'Бонусы возвращены клиенту'
        ];
    }

    // Метод для начисления бонусов после завершения продажи
    public function awardBonusPoints()
    {
        if (!$this->client_id || !$this->client->bonusCard) {
            return 0;
        }

        $client = $this->client;
        
        // Рассчитываем сумму для начисления бонусов
        // Берем итоговую сумму продажи (без вычета скидки бонусами)
        $bonusableAmount = $this->total + ($this->used_bonus_points ?? 0); // Добавляем бонусы обратно
        
        // Используем BonusPercent из бонусной карты
        $percent = $client->bonusCard->BonusPercent;
        
        // Расчет бонусов
        $pointsToAward = $bonusableAmount * ($percent / 100);
        $pointsToAward = floor($pointsToAward);

        if ($pointsToAward > 0) {
            // Получаем баланс до начисления
            $oldBalance = $client->bonus_points;
            
            // Начисляем бонусы
            $client->bonus_points += $pointsToAward;
            $client->save();

            // СОХРАНЯЕМ В ИСТОРИЮ НАЧИСЛЕНИЕ БОНУСОВ
            \App\Models\BonusHistory::create([
                'client_id' => $this->client_id,
                'amount' => $pointsToAward,
                'operation_type' => 'credit',
                'balance_after' => $client->bonus_points, // баланс после начисления
                'reason' => 'Начисление бонусов за продажу #' . $this->id,
                'sale_id' => $this->id,
            ]);
        }

        return $pointsToAward;
    }

    // Методы
    public function recalculateTotal()
    {
        // Сумма товаров
        $productsTotal = $this->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        // Сумма кальянов
        $hookahsTotal = $this->hookahs->sum('price');
        
        $total = $productsTotal + $hookahsTotal;
        
        // Вычитаем скидку и бонусы
        $finalTotal = $total - $this->discount - ($this->used_bonus_points ?? 0);
        $finalTotal = max(0, $finalTotal); // Не даем уйти в минус

        if ($this->total != $finalTotal) {
            $this->update(['total' => $finalTotal]);
        }

        return $finalTotal;
    }
    public function completeSale()
    {
        // Проверяем наличие всех товаров
        foreach ($this->items as $item) {
            $stock = Stock::where('warehouse_id', $this->warehouse_id)
                         ->where('product_id', $item->product_id)
                         ->first();

            if (!$stock || $stock->quantity < $item->quantity) {
                return [
                    'success' => false,
                    'message' => "Недостаточно товара: {$item->product->name}. Доступно: " . ($stock->quantity ?? 0)
                ];
            }
        }

        // Списываем товары
        foreach ($this->items as $item) {
            $stock = Stock::where('warehouse_id', $this->warehouse_id)
                         ->where('product_id', $item->product_id)
                         ->first();

            $result = $stock->useQuantity($item->quantity);
            if (!$result['success']) {
                return $result;
            }
        }

        $this->status = 'completed';
        $this->save();

        return [
            'success' => true,
            'message' => 'Продажа завершена успешно'
        ];
    }
}