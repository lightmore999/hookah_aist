<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Сотрудники на смене
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'shift_user', 'shift_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Связи через промежуточную таблицу
     */
    public function shiftUsers()
    {
        return $this->hasMany(ShiftUser::class);
    }

    /**
     * Проверить, запланирована ли смена
     */
    public function isPlanned()
    {
        return $this->status === 'planned';
    }

    /**
     * Проверить, открыта ли смена
     */
    public function isOpen()
    {
        return $this->status === 'open';
    }

    /**
     * Проверить, закрыта ли смена
     */
    public function isClosed()
    {
        return $this->status === 'closed';
    }

    /**
     * Проверить, сегодняшняя ли смена
     */
    public function isToday()
    {
        return $this->date->isToday();
    }

    /**
     * Получить статус смены в читаемом виде
     */
    public function getStatusTextAttribute()
    {
        return [
            'planned' => 'Запланирована',
            'open' => 'Открыта',
            'closed' => 'Закрыта',
        ][$this->status] ?? $this->status;
    }

    /**
     * Получить цвет статуса для отображения
     */
    public function getStatusColorAttribute()
    {
        return [
            'planned' => 'secondary',
            'open' => 'success',
            'closed' => 'dark',
        ][$this->status] ?? 'light';
    }

    /**
     * Открыть смену
     */
    public function open()
    {
        $this->update([
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    /**
     * Закрыть смену
     */
    public function close()
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Scope для получения текущей открытой смены
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope для получения запланированных смен
     */
    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    /**
     * Scope для получения закрытых смен
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope для получения будущих смен
     */
    public function scopeFuture($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                    ->orderBy('date');
    }

    /**
     * Scope для получения прошедших смен
     */
    public function scopePast($query)
    {
        return $query->where('date', '<', now()->toDateString())
                    ->orderByDesc('date');
    }

    /**
     * Scope для получения смен на сегодня
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', now()->toDateString());
    }
}