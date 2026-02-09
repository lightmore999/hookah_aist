<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Table extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'table_bookings';
    
    protected $fillable = [
        'table_name_id', 
        'booking_date',
        'booking_time',
        'duration',
        'guests_count',
        'comment',
        'phone',
        'guest_name',
        'client_id',
        'status'
    ];

    protected $casts = [
        'booking_date' => 'date',
        'booking_time' => 'datetime:H:i',
        'duration' => 'integer',
    ];

    /**
     * Get the table name associated with the booking.
     */
    public function tableName(): BelongsTo
    {
        return $this->belongsTo(TableName::class, 'table_name_id');
    }

    /**
     * Get the client associated with the table.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Accessor для обратной совместимости с table_number
     * Возвращает номер стола из названия (например "Стол 1" -> "1")
     */
    public function getTableNumberAttribute(): ?string
    {
        // Загружаем отношение, если оно еще не загружено
        $tableName = $this->tableName;
        if ($tableName && $tableName->name) {
            // Извлекаем цифры из названия стола
            preg_match('/\d+/', $tableName->name, $matches);
            return $matches[0] ?? $tableName->name;
        }
        return null;
    }


    /**
     * Get the display name for the guest.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->guest_name ?? $this->client?->name ?? 'Без имени';
    }

    /**
     * Get the display phone for the guest.
     */
    public function getDisplayPhoneAttribute(): ?string
    {
        return $this->phone ?? $this->client?->phone;
    }

    /**
     * Get status text for display.
     */
    public function getStatusText(): string
    {
        $statuses = [
            'new' => 'Новый стол',
            'opened_without_hookah' => 'Открытый стол (без кальяна)',
            'opened_with_hookah' => 'Открытый стол (с кальяном)',
            'closed' => 'Стол закрыт'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get badge color for status.
     */
    public function getStatusBadgeColor(): string
    {
        $colors = [
            'new' => 'primary',
            'opened_without_hookah' => 'success',
            'opened_with_hookah' => 'info',
            'closed' => 'secondary'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Get background color for status.
     */
    public function getStatusColor(): string
    {
        $colors = [
            'new' => '#e3f2fd', // светло-голубой
            'opened_without_hookah' => '#e8f5e9', // светло-зеленый
            'opened_with_hookah' => '#e0f7fa', // светло-бирюзовый
            'closed' => '#f5f5f5' // светло-серый
        ];

        return $colors[$this->status] ?? '#ffffff';
    }

    /**
     * Sale relation через table_name_id (обновленная версия)
     * NOTE: Возможно нужно будет обновить модель Sale тоже
     */
    public function sale()
    {
        return $this->hasOne(Sale::class, 'table_id', 'table_name_id')
                    ->where('status', '!=', 'completed')
                    ->whereDate('created_at', $this->booking_date);
    }

    /**
     * Sale relation для обратной совместимости через номер стола
     * Используйте это, если модель Sale все еще использует table_number
     */
    public function saleByNumber()
    {
        return $this->hasOne(Sale::class, 'table_number', 'table_number')
                    ->where('status', '!=', 'completed')
                    ->whereDate('created_at', $this->booking_date);
    }

    public function hasActiveOrder(): bool
    {
        return $this->sale()->exists() || $this->saleByNumber()->exists();
    }

    public function scopeWithActiveOrder($query)
    {
        return $query->with([
            'sale' => function($q) {
                $q->where('status', '!=', 'completed')
                  ->whereDate('created_at', \DB::raw('DATE(table_bookings.booking_date)'));
            },
            'saleByNumber' => function($q) {
                $q->where('status', '!=', 'completed')
                  ->whereDate('created_at', \DB::raw('DATE(table_bookings.booking_date)'));
            }
        ]);
    }
    
    public function activeSale()
    {
        return $this->hasOne(Sale::class, 'table_id', 'table_name_id')
            ->where('status', '!=', 'completed')
            ->whereDate('created_at', $this->booking_date);
    }
    
    public function getEndTimeAttribute(): ?string
    {
        if (!$this->booking_time || !$this->duration) {
            return null;
        }
        
        $startTime = \Carbon\Carbon::parse($this->booking_time);
        $endTime = $startTime->copy()->addMinutes($this->duration);
        
        return $endTime->format('H:i');
    }

    public function getEndTimeForEditAttribute(): ?\Carbon\Carbon
    {
        if (!$this->booking_time || !$this->duration) {
            return null;
        }
        
        $startTime = \Carbon\Carbon::parse($this->booking_time);
        return $startTime->copy()->addMinutes($this->duration);
    }

    /**
     * Scope для получения бронирований определенного стола
     */
    public function scopeForTable($query, $tableNameId)
    {
        return $query->where('table_name_id', $tableNameId);
    }

    /**
     * Scope для получения бронирований на определенную дату
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('booking_date', $date);
    }

    /**
     * Scope для получения активных бронирований (не отмененных)
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }

    /**
     * Проверить доступность стола на дату и время
     */
    public static function isTableAvailable($tableNameId, $date, $time, $duration, $excludeId = null): bool
    {

        $query = self::where('table_name_id', $tableNameId)
            ->where('booking_date', $date)
            ->where('status', '!=', 'cancelled');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingBookings = $query->get();

        // Исправляем расчет времени
        // strtotime нужно передавать полную дату и время
        $requestStart = strtotime($date . ' ' . $time);
        // Длительность в МИНУТАХ, а не часах! Умножаем на 60
        $requestEnd = $requestStart + ($duration * 60);

        foreach ($existingBookings as $booking) {
            // Для существующих броней также нужно использовать полную дату и время
            $bookingStart = strtotime($booking->booking_date . ' ' . $booking->booking_time);
            $bookingEnd = $bookingStart + ($booking->duration * 60); // Здесь тоже минуты!

            \Log::info('Checking booking:', [
                'booking_id' => $booking->id,
                'guest_name' => $booking->guest_name,
                'booking_start' => date('Y-m-d H:i:s', $bookingStart),
                'booking_end' => date('Y-m-d H:i:s', $bookingEnd),
                'booking_duration_minutes' => $booking->duration
            ]);

            // Проверяем пересечение
            // Условие: (requestStart < bookingEnd) && (requestEnd > bookingStart)
            if ($requestStart < $bookingEnd && $requestEnd > $bookingStart) {
                \Log::info('Conflict found! Booking overlaps.');
                return false;
            }
        }

        return true;
    }

    /**
     * Получить все бронирования для стола на дату
     */
    public static function getBookingsForTable($tableNameId, $date)
    {
        return self::forTable($tableNameId)
            ->forDate($date)
            ->active()
            ->orderBy('booking_time')
            ->get();
    }

    /**
     * Получить время окончания бронирования как Carbon объект
     */
    public function getEndTimeCarbonAttribute(): ?\Carbon\Carbon
    {
        if (!$this->booking_time || !$this->duration) {
            return null;
        }
        
        $startTime = \Carbon\Carbon::parse($this->booking_time);
        return $startTime->copy()->addMinutes($this->duration);
    }

    /**
     * Eager load отношения по умолчанию
     */
    protected $with = ['tableName', 'client'];
}