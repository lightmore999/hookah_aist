<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\LogsOperations;

class Sale extends Model
{
    use HasFactory; // Добавляем трейт

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

    /**
     * Временное свойство для хранения комментария удаления
     */
    public $delete_comment;

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
        
        // Используем реальную сумму продажи для расчета
        $totalForBonus = $this->getTotalForBonusCalculation();
        
        // Можно использовать не более X% суммы заказа (без учета бонусов)
        $maxBonusesByTotal = floor($totalForBonus * ($maxPercent / 100));
        
        // Но не больше, чем есть у клиента
        $clientBonusPoints = $this->client->bonus_points;
        
        return min($clientBonusPoints, (int) $maxBonusesByTotal);
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
        if (!$client) {
            return [
                'success' => false,
                'message' => 'Клиент не найден'
            ];
        }
        
        // Пересчитываем сумму перед проверкой максимального количества бонусов
        $this->recalculateTotal();
        $this->refresh();
        
        $maxUsable = $this->getMaxUsableBonuses();
        
        if ($maxUsable <= 0) {
            return [
                'success' => false,
                'message' => "Можно использовать не более 0 бонусов"
            ];
        }
        
        if ($pointsToUse > $maxUsable) {
            return [
                'success' => false,
                'message' => "Можно использовать не более {$maxUsable} бонусов"
            ];
        }

        if ($client->bonus_points < $pointsToUse) {
            return [
                'success' => false,
                'message' => "Недостаточно бонусов у клиента. Доступно: {$client->bonus_points}"
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

    public function getTotalForBonusCalculation(): float
    {
        $productsTotal = $this->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        $hookahsTotal = $this->hookahs->sum('price');
        
        $subtotal = $productsTotal + $hookahsTotal;
        $total = $subtotal - (float) $this->discount;
        
        return max(0, $total);
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
        // Бонусы начисляются от суммы ПОСЛЕ применения скидки, но ПЕРЕД вычетом бонусов
        // То есть: сумма товаров + кальяны - обычная скидка
        
        // Сумма товаров
        $productsTotal = $this->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        // Сумма кальянов
        $hookahsTotal = $this->hookahs->sum('price');
        
        // Итоговая сумма для расчета бонусов
        $bonusableAmount = $productsTotal + $hookahsTotal - (float) $this->discount;
        
        // Не даем уйти в минус
        $bonusableAmount = max(0, $bonusableAmount);
        
        // Используем BonusPercent из бонусной карты
        $percent = $client->bonusCard->BonusPercent;
        
        // Расчет бонусов
        $pointsToAward = floor($bonusableAmount * ($percent / 100));

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
                'reason' => "Начисление {$percent}% бонусов за продажу #{$this->id}",
                'sale_id' => $this->id,
            ]);
            
            // Для отладки можно сохранить сумму, от которой считали бонусы
            \Log::info("Бонусы начислены за продажу #{$this->id}", [
                'sale_id' => $this->id,
                'client_id' => $this->client_id,
                'bonusable_amount' => $bonusableAmount,
                'percent' => $percent,
                'points_awarded' => $pointsToAward,
                'items_total' => $productsTotal,
                'hookahs_total' => $hookahsTotal,
                'discount' => $this->discount,
                'used_bonuses' => $this->used_bonus_points
            ]);
        }

        return (int) $pointsToAward;
    }

    // =========== МЕТОДЫ ДЛЯ ПРОДАЖ С ЛОГИРОВАНИЕМ ===========

    /**
     * Пересчитать итоговую сумму с логированием
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
            $oldTotal = $this->total;
            $this->update(['total' => $finalTotal]);
        }

        return $finalTotal;
    }

    /**
     * Завершить продажу с логированием
     */
    public function completeSale(?string $comment = null): array
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

        $oldStatus = $this->status;
        $this->status = 'completed';
        $this->save();

        return [
            'success' => true,
            'message' => 'Продажа завершена успешно' . ($bonusAwarded ? " (+{$bonusAwarded} бонусов)" : ""),
            'bonus_awarded' => $bonusAwarded,
        ];
    }

    /**
     * Добавить кальян к продаже с логированием
     */
    public function addHookah($hookah, ?string $comment = null): array
    {
        $hookahId = is_object($hookah) ? $hookah->id : $hookah;
        $hookahName = is_object($hookah) ? $hookah->name : 'Кальян #' . $hookahId;
        
        // Проверяем, не добавлен ли уже этот кальян
        if ($this->hookahs()->where('hookah_id', $hookahId)->exists()) {
            return [
                'success' => false,
                'message' => 'Этот кальян уже добавлен к продаже'
            ];
        }
        
        $this->hookahs()->attach($hookahId);
        
        // Пересчитываем сумму
        $this->recalculateTotal();

        return [
            'success' => true,
            'message' => 'Кальян успешно добавлен',
            'hookah_name' => $hookahName
        ];
    }

    /**
     * Удалить кальян из продажи с логированием
     */
    public function removeHookah($hookah, ?string $comment = null): array
    {
        $hookahId = is_object($hookah) ? $hookah->id : $hookah;
        $hookahName = is_object($hookah) ? $hookah->name : 'Кальян #' . $hookahId;
        
        // Проверяем, есть ли этот кальян в продаже
        if (!$this->hookahs()->where('hookah_id', $hookahId)->exists()) {
            return [
                'success' => false,
                'message' => 'Этот кальян не найден в продаже'
            ];
        }
        
        $this->hookahs()->detach($hookahId);
        
        // Пересчитываем сумму
        $this->recalculateTotal();

        return [
            'success' => true,
            'message' => 'Кальян успешно удален',
            'hookah_name' => $hookahName
        ];
    }

    /**
     * Добавить товар к продаже с логированием
     */
    public function addItem($product, int $quantity = 1, ?float $unitPrice = null, ?string $comment = null): array
    {
        $productId = is_object($product) ? $product->id : $product;
        $productObj = is_object($product) ? $product : Product::find($productId);
        
        if (!$productObj) {
            return [
                'success' => false,
                'message' => 'Товар не найден'
            ];
        }
        
        $unitPrice = $unitPrice ?? $productObj->price;
        
        // Проверяем наличие товара
        if ($productObj->total_quantity < $quantity) {
            return [
                'success' => false,
                'message' => "Недостаточно товара: {$productObj->name}. Доступно: {$productObj->total_quantity}"
            ];
        }
        
        // Добавляем товар
        SaleItem::create([
            'sale_id' => $this->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ]);
        
        // Пересчитываем сумму
        $this->recalculateTotal();

        return [
            'success' => true,
            'message' => 'Товар успешно добавлен',
            'product_name' => $productObj->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice
        ];
    }

    /**
     * Удалить товар из продажи с логированием
     */
    public function removeItem($saleItem, ?string $comment = null): array
    {
        $saleItemId = is_object($saleItem) ? $saleItem->id : $saleItem;
        $saleItemObj = is_object($saleItem) ? $saleItem : SaleItem::find($saleItemId);
        
        if (!$saleItemObj || $saleItemObj->sale_id != $this->id) {
            return [
                'success' => false,
                'message' => 'Товар не найден в продаже'
            ];
        }
        
        $productName = $saleItemObj->product->name ?? 'Товар #' . $saleItemObj->product_id;
        $quantity = $saleItemObj->quantity;
        
        // Удаляем товар
        $saleItemObj->delete();
        
        
        // Пересчитываем сумму
        $this->recalculateTotal();

        return [
            'success' => true,
            'message' => 'Товар успешно удален',
            'product_name' => $productName,
            'quantity' => $quantity
        ];
    }

    /**
     * Отменить продажу с логированием
     */
    public function cancelSale(?string $comment = null): array
    {
        if ($this->status === 'completed') {
            return [
                'success' => false,
                'message' => 'Нельзя отменить завершенную продажу'
            ];
        }

        $oldStatus = $this->status;
        
        // Возвращаем бонусы если они были использованы
        if ($this->used_bonus_points > 0 && $this->client) {
            $this->cancelBonuses();
        }
        
        // Возвращаем товары на склад
        foreach ($this->items as $item) {
            $product = $item->product;
            if ($product) {
                $product->total_quantity += $item->quantity;
                $product->save();
            }
        }

        $this->status = 'cancelled';
        $this->save();

        return [
            'success' => true,
            'message' => 'Продажа успешно отменена'
        ];
    }

    /**
     * Изменить статус продажи с логированием
     */
    public function changeStatus(string $newStatus, ?string $comment = null): array
    {
        $oldStatus = $this->status;
        $statusText = [
            'new' => 'Новый',
            'in_progress' => 'В работе',
            'completed' => 'Завершен',
            'cancelled' => 'Отменен'
        ];
        
        $oldStatusText = $statusText[$oldStatus] ?? $oldStatus;
        $newStatusText = $statusText[$newStatus] ?? $newStatus;
        
        $this->status = $newStatus;
        $this->save();

        return [
            'success' => true,
            'message' => "Статус изменен на '{$newStatusText}'"
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