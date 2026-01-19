<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'table_id',
        'total',
        'discount',
        'used_bonus_points',
        'payment_method_id', 
        'status',
        'comment',
        'sale_date',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'sale_date' => 'datetime',
        'used_bonus_points' => 'integer',
    ];

    // =========== ОТНОШЕНИЯ ===========

    /**
     * Клиент, совершивший покупку
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Стол, за которым была продажа
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    /**
     * Способ оплаты
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'IDPaymentMethod');
    }

    /**
     * Товары в продаже
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Кальяны в продаже
     */
    public function hookahs(): BelongsToMany
    {
        return $this->belongsToMany(Hookah::class, 'sale_hookahs')
                    ->withTimestamps();
    }

    /**
     * История бонусных операций
     */
    public function bonusHistories(): HasMany
    {
        return $this->hasMany(BonusHistory::class, 'sale_id');
    }

    // =========== ACCESSORS ===========

    /**
     * Текстовое представление статуса
     */
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'new' => 'Новый',
            'in_progress' => 'В работе',
            'completed' => 'Завершен',
            'cancelled' => 'Отменен'
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Бонусная скидка (1 бонус = 1 рубль)
     */
    public function getBonusDiscountAttribute(): float
    {
        return (float) ($this->used_bonus_points ?? 0);
    }

    /**
     * Итоговая сумма с учетом всех скидок и бонусов
     */
    public function getFinalTotalAttribute(): float
    {
        $total = (float) $this->total - (float) $this->discount - (float) $this->bonus_discount;
        return max(0, $total); // Не даем уйти в минус
    }

    /**
     * Сумма кальянов
     */
    public function getHookahsTotalAttribute(): float
    {
        return $this->hookahs->sum('price');
    }

    /**
     * Название способа оплаты
     */
    public function getPaymentMethodNameAttribute(): string
    {
        return $this->paymentMethod ? $this->paymentMethod->Name : 'Не указано';
    }

    /**
     * Форматированная дата продажи
     */
    public function getFormattedSaleDateAttribute(): string
    {
        return $this->sale_date ? $this->sale_date->format('d.m.Y H:i') : '-';
    }

    // =========== ПРОВЕРКИ ===========

    /**
     * Проверка, есть ли привязанный стол
     */
    public function hasTable(): bool
    {
        return !is_null($this->table_id);
    }

    /**
     * Проверка, можно ли использовать бонусы
     */
    public function canUseBonuses(): bool
    {
        return $this->client_id && $this->client && $this->client->bonus_points > 0;
    }

    /**
     * Проверка, использовались ли бонусы
     */
    public function getUsedBonusCardAttribute(): bool
    {
        return $this->used_bonus_points > 0;
    }

    // =========== МЕТОДЫ ДЛЯ БОНУСОВ ===========

    /**
     * Максимальное количество бонусов для использования
     */
    public function getMaxUsableBonuses(): int
    {
        if (!$this->canUseBonuses()) {
            return 0;
        }

        // Получаем карту клиента для правил списания
        $bonusCard = $this->client->bonusCard;
        $maxPercent = $bonusCard ? $bonusCard->MaxSpendPercent : 50; // 50% по умолчанию
        
        // Можно использовать не более X% суммы заказа
        $maxBonusesByTotal = floor((float) $this->total * ($maxPercent / 100));
        
        // Но не больше, чем есть у клиента
        return min($this->client->bonus_points, (int) $maxBonusesByTotal);
    }

    /**
     * Применить бонусы к продаже
     */
    public function applyBonuses(int $pointsToUse): array
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

        // Сохраняем в историю списание бонусов
        BonusHistory::create([
            'client_id' => $this->client_id,
            'amount' => $pointsToUse,
            'operation_type' => 'debit',
            'balance_after' => $client->bonus_points,
            'reason' => 'Списание бонусов при оплате продажи #' . $this->id,
            'sale_id' => $this->id,
        ]);

        return [
            'success' => true,
            'message' => "Использовано {$pointsToUse} бонусов",
            'bonus_discount' => $pointsToUse,
        ];
    }

    /**
     * Отменить использование бонусов
     */
    public function cancelBonuses(): array
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

    /**
     * Начислить бонусы после завершения продажи
     */
    public function awardBonusPoints(): int
    {
        if (!$this->client_id || !$this->client->bonusCard) {
            return 0;
        }

        $client = $this->client;
        
        // Рассчитываем сумму для начисления бонусов
        // Берем итоговую сумму продажи (без вычета скидки бонусами)
        $bonusableAmount = (float) $this->total + (float) ($this->used_bonus_points ?? 0);
        
        // Используем BonusPercent из бонусной карты
        $percent = $client->bonusCard->BonusPercent;
        
        // Расчет бонусов
        $pointsToAward = $bonusableAmount * ($percent / 100);
        $pointsToAward = floor($pointsToAward);

        if ($pointsToAward > 0) {
            // Начисляем бонусы
            $client->bonus_points += $pointsToAward;
            $client->save();

            // Сохраняем в историю начисление бонусов
            BonusHistory::create([
                'client_id' => $this->client_id,
                'amount' => $pointsToAward,
                'operation_type' => 'credit',
                'balance_after' => $client->bonus_points,
                'reason' => 'Начисление бонусов за продажу #' . $this->id,
                'sale_id' => $this->id,
            ]);
        }

        return (int) $pointsToAward;
    }

    // =========== МЕТОДЫ ДЛЯ ПРОДАЖ ===========

    /**
     * Пересчитать итоговую сумму
     */
    public function recalculateTotal(): float
    {
        // Сумма товаров
        $productsTotal = $this->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        // Сумма кальянов
        $hookahsTotal = $this->hookahs->sum('price');
        
        $total = $productsTotal + $hookahsTotal;
        
        // Вычитаем скидку и бонусы
        $finalTotal = $total - (float) $this->discount - (float) ($this->used_bonus_points ?? 0);
        $finalTotal = max(0, $finalTotal); // Не даем уйти в минус

        if ((float) $this->total != $finalTotal) {
            $this->update(['total' => $finalTotal]);
        }

        return $finalTotal;
    }

    /**
     * Завершить продажу
     */
    public function completeSale(): array
    {
        // Проверяем наличие всех товаров
        foreach ($this->items as $item) {
            // Убираем проверку склада, так как склад больше не привязан к продаже
            // Теперь нужно проверять общее наличие товара в системе
            
            $product = $item->product;
            if (!$product || $product->total_quantity < $item->quantity) {
                return [
                    'success' => false,
                    'message' => "Недостаточно товара: {$item->product->name}. Доступно: " . ($product->total_quantity ?? 0)
                ];
            }
        }

        // Списываем товары (теперь без привязки к конкретному складу)
        foreach ($this->items as $item) {
            $product = $item->product;
            if ($product) {
                $product->total_quantity -= $item->quantity;
                $product->save();
            }
        }

        // Начисляем бонусы если есть бонусная карта
        $bonusAwarded = 0;
        if ($this->client_id && $this->client->bonusCard) {
            $bonusAwarded = $this->awardBonusPoints();
        }

        $this->status = 'completed';
        $this->save();

        return [
            'success' => true,
            'message' => 'Продажа завершена успешно' . ($bonusAwarded ? " (+{$bonusAwarded} бонусов)" : ""),
            'bonus_awarded' => $bonusAwarded,
        ];
    }

    // =========== SCOPES ===========

    /**
     * Scope для фильтрации по способу оплаты
     */
    public function scopeByPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    /**
     * Scope для фильтрации по дате продажи
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('sale_date', [$startDate, $endDate]);
    }

    /**
     * Scope для продаж с использованием бонусов
     */
    public function scopeWithBonusUsage($query)
    {
        return $query->where('used_bonus_points', '>', 0);
    }

    /**
     * Scope для продаж по конкретному клиенту
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope для активных продаж (не завершенных)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['new', 'in_progress']);
    }

    /**
     * Scope для завершенных продаж
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}