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
        'notes', // Теперь это будет один комментарий
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
        return $this->belongsToMany(User::class, 'shift_user', 'shift_id', 'user_id')
                    ->where('role', 'employee') // Только сотрудники!
                    ->withTimestamps();
                    // Убрали ->withPivot(['start_time', 'end_time'])
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
     * Установить комментарий (один на смену)
     */
    public function setNote($note)
    {
        $this->update([
            'notes' => $note
        ]);
    }

    /**
     * Добавить автоматический комментарий при закрытии
     */
    public function addAutoCloseNote()
    {
        $note = "Смена автоматически закрыта системой" . 
                now()->format('d.m.Y H:i:s') . 
                " (смена не была закрыта вручную до 12:00 следующего дня)";
        
        $this->update([
            'notes' => $note
        ]);
    }

    public static function getActiveShift()
    {
        $now = now();
        
        // Если сейчас утро/ранний день (00:00 - 12:00), ищем вчерашнюю смену
        if ($now->hour < 12) {
            $yesterday = $now->copy()->subDay();
            return Shift::whereDate('date', $yesterday->format('Y-m-d'))
                ->where('status', 'open')
                ->whereNull('closed_at')
                ->first();
        }
        
        // Если сейчас после 12:00 (12:01 - 23:59), ищем сегодняшнюю смену
        return Shift::whereDate('date', $now->format('Y-m-d'))
            ->where('status', 'open')
            ->whereNull('closed_at')
            ->first();
    }

    /**
     * Получить смену для указанного времени продажи
     */
    public static function getShiftForSaleTime($saleTime)
    {
        $time = \Carbon\Carbon::parse($saleTime);
        
        // Если продажа в период 00:00 - 12:00, это вчерашняя смена
        if ($time->hour < 12) {
            $date = $time->copy()->subDay()->format('Y-m-d');
        } else {
            // Если продажа в период 12:01 - 23:59, это сегодняшняя смена
            $date = $time->format('Y-m-d');
        }
        
        return Shift::whereDate('date', $date)
            ->where('status', 'open')
            ->whereNull('closed_at')
            ->first();
    }

    /**
     * Проверить, можно ли создать продажу в данный момент
     */
    public static function canCreateSale()
    {
        $activeShift = self::getActiveShift();
        return !is_null($activeShift);
    }
}
