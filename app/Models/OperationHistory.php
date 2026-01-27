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

    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_CLOSE = 'close';
    const ACTION_OPEN = 'open';
    const ACTION_ADD_HOOKAH = 'add_hookah';
    const ACTION_REMOVE_HOOKAH = 'remove_hookah';

    const ENTITY_TABLE = 'table';
    const ENTITY_SALE = 'sale';
    const ENTITY_EXPENSE = 'expense';
    const ENTITY_HOOKAH = 'hookah';

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
}