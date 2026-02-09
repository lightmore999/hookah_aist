<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationHistory extends Model
{
    protected $fillable = [
        'user_id',
        'action_type',
        'entity_type',
        'entity_id',
        'old_data',
        'new_data',
        'comment'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    // Константы типов действий
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_CLOSE = 'close';
    const ACTION_OPEN = 'open';
    const ACTION_ADD_HOOKAH = 'add_hookah';
    const ACTION_REMOVE_HOOKAH = 'remove_hookah';
    
    // Добавляем новые константы для операций с товарами
    const ACTION_ADD_PRODUCT = 'add_product';
    const ACTION_REMOVE_PRODUCT = 'remove_product';
    const ACTION_UPDATE_PRODUCT_QUANTITY = 'update_product_quantity';
    const ACTION_UPDATE_PRODUCT_PRICE = 'update_product_price';

    // Константы типов сущностей
    const ENTITY_TABLE = 'table';
    const ENTITY_SALE = 'sale';
    const ENTITY_EXPENSE = 'expense';
    const ENTITY_HOOKAH = 'hookah';
    // Можно добавить ENTITY_PRODUCT если нужно

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionTextAttribute(): string
    {
        $actions = [
            self::ACTION_CREATE => 'Создание',
            self::ACTION_UPDATE => 'Изменение',
            self::ACTION_DELETE => 'Удаление',
            self::ACTION_CLOSE => 'Закрытие',
            self::ACTION_OPEN => 'Открытие',
            self::ACTION_ADD_HOOKAH => 'Добавление кальяна',
            self::ACTION_REMOVE_HOOKAH => 'Удаление кальяна',
            
            // Добавляем переводы для новых действий
            self::ACTION_ADD_PRODUCT => 'Добавление товара',
            self::ACTION_REMOVE_PRODUCT => 'Удаление товара',
            self::ACTION_UPDATE_PRODUCT_QUANTITY => 'Изменение количества товара',
            self::ACTION_UPDATE_PRODUCT_PRICE => 'Изменение цены товара',
        ];

        return $actions[$this->action_type] ?? $this->action_type;
    }

    public function getEntityTextAttribute(): string
    {
        $entities = [
            self::ENTITY_TABLE => 'Стол',
            self::ENTITY_SALE => 'Продажа',
            self::ENTITY_EXPENSE => 'Расход',
            self::ENTITY_HOOKAH => 'Кальян',
        ];

        return $entities[$this->entity_type] ?? $this->entity_type;
    }
    
    /**
     * Получить все доступные типы действий
     */
    public static function getActionTypes(): array
    {
        return [
            self::ACTION_CREATE => 'Создание',
            self::ACTION_UPDATE => 'Изменение',
            self::ACTION_DELETE => 'Удаление',
            self::ACTION_CLOSE => 'Закрытие',
            self::ACTION_OPEN => 'Открытие',
            self::ACTION_ADD_HOOKAH => 'Добавление кальяна',
            self::ACTION_REMOVE_HOOKAH => 'Удаление кальяна',
            self::ACTION_ADD_PRODUCT => 'Добавление товара',
            self::ACTION_REMOVE_PRODUCT => 'Удаление товара',
            self::ACTION_UPDATE_PRODUCT_QUANTITY => 'Изменение количества товара',
            self::ACTION_UPDATE_PRODUCT_PRICE => 'Изменение цены товара',
        ];
    }
    
    /**
     * Получить все доступные типы сущностей
     */
    public static function getEntityTypes(): array
    {
        return [
            self::ENTITY_TABLE => 'Столы',
            self::ENTITY_SALE => 'Продажи',
            self::ENTITY_EXPENSE => 'Расходы',
            self::ENTITY_HOOKAH => 'Кальяны',
        ];
    }

    public function loadSaleDetails()
    {
        if ($this->entity_type === self::ENTITY_SALE && $this->entity_id) {
            try {
                $this->sale = \App\Models\Sale::with([
                    'client',
                    'table',
                    'paymentMethod',
                    'items.product',
                    'hookahs',
                    'bonusHistories'
                ])->find($this->entity_id);
            } catch (\Exception $e) {
                $this->sale = null;
            }
        }
        return $this;
    }
}