<?php

namespace App\Traits;

use App\Models\OperationHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait LogsOperations
{
    protected static function bootLogsOperations()
    {
        static::created(function (Model $model) {
            $model->logOperation(OperationHistory::ACTION_CREATE, null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $model->logOperation(
                OperationHistory::ACTION_UPDATE,
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function (Model $model) {
            // Используем комментарий для удаления если он установлен
            $deleteComment = $model->delete_comment ?? null;
            $model->logOperation(OperationHistory::ACTION_DELETE, $model->getOriginal(), null, $deleteComment);
        });
    }

    /**
     * Логирование операции
     */
    public function logOperation(string $action, ?array $oldData = null, ?array $newData = null, ?string $comment = null): OperationHistory
    {
        $entityType = $this->getEntityType();
        
        // Фильтруем чувствительные данные
        if ($oldData) {
            $oldData = $this->filterSensitiveData($oldData);
        }
        
        if ($newData) {
            $newData = $this->filterSensitiveData($newData);
        }

        return OperationHistory::create([
            'user_id' => Auth::id(),
            'action_type' => $action,
            'entity_type' => $entityType,
            'entity_id' => $this->getKey(),
            'old_data' => $oldData,
            'new_data' => $newData,
            'comment' => $comment,
        ]);
    }

    /**
     * Установить комментарий для удаления
     */
    public function setDeleteComment(string $comment): self
    {
        $this->delete_comment = $comment;
        return $this;
    }

    /**
     * Логирование кастомной операции (закрытие стола, добавление кальяна и т.д.)
     */
    public function logCustomOperation(string $action, ?string $comment = null): OperationHistory
    {
        return $this->logOperation($action, $this->getAttributes(), $this->getAttributes(), $comment);
    }

    /**
     * Фильтрация чувствительных данных
     */
    protected function filterSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'remember_token',
            'api_token',
            'access_token',
            'refresh_token',
        ];

        foreach ($sensitiveFields as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Получить тип сущности для логирования
     */
    protected function getEntityType(): string
    {
        // Маппинг классов на типы сущностей
        $mapping = [
            \App\Models\Table::class => OperationHistory::ENTITY_TABLE,
            \App\Models\Sale::class => OperationHistory::ENTITY_SALE,
            \App\Models\Expenditure::class => OperationHistory::ENTITY_EXPENSE,
        ];

        if (class_exists(\App\Models\Hookah::class)) {
            $mapping[\App\Models\Hookah::class] = OperationHistory::ENTITY_HOOKAH;
        }

        return $mapping[get_class($this)] ?? strtolower(class_basename($this));
    }

    /**
     * Получить историю операций для этой модели
     */
    public function operationHistory()
    {
        return $this->morphMany(OperationHistory::class, 'entity', 'entity_type', 'entity_id');
    }
}