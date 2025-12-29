<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'table_bookings';
    
    protected $fillable = [
        'table_number',
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
    ];

    /**
     * Get the client associated with the table.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the display name for the guest.
     */
    public function getDisplayNameAttribute()
    {
        return $this->guest_name ?? $this->client?->name ?? 'Без имени';
    }

    /**
     * Get the display phone for the guest.
     */
    public function getDisplayPhoneAttribute()
    {
        return $this->phone ?? $this->client?->phone;
    }

    /**
     * Get status text for display.
     */
    public function getStatusText()
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
    public function getStatusBadgeColor()
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
    public function getStatusColor()
    {
        $colors = [
            'new' => '#e3f2fd', // светло-голубой
            'opened_without_hookah' => '#e8f5e9', // светло-зеленый
            'opened_with_hookah' => '#e0f7fa', // светло-бирюзовый
            'closed' => '#f5f5f5' // светло-серый
        ];

        return $colors[$this->status] ?? '#ffffff';
    }

    public function sale()
    {
        return $this->hasOne(Sale::class, 'table_number', 'table_number')
                    ->where('status', '!=', 'completed') // только активные заказы
                    ->whereDate('created_at', $this->booking_date); // заказы на ту же дату
    }

    public function hasActiveOrder()
    {
        return $this->sale()->exists();
    }

    // Или более оптимальный вариант с предзагрузкой:
    public function scopeWithActiveOrder($query)
    {
        return $query->with(['sale' => function($q) {
            $q->where('status', '!=', 'completed')
            ->whereDate('created_at', \DB::raw('DATE(table_bookings.booking_date)'));
        }]);
    }
    
    public function activeSale()
    {
        return $this->hasOne(Sale::class, 'table_id')
            ->where('status', '!=', 'completed');
    }
    
    public function getEndTimeAttribute()
    {
        if (!$this->booking_time || !$this->duration) {
            return null;
        }
        
        $startTime = \Carbon\Carbon::parse($this->booking_time);
        $endTime = $startTime->copy()->addMinutes($this->duration);
        
        return $endTime->format('H:i');
    }

    public function getEndTimeForEditAttribute()
    {
        if (!$this->booking_time || !$this->duration) {
            return null;
        }
        
        $startTime = \Carbon\Carbon::parse($this->booking_time);
        $endTime = $startTime->copy()->addMinutes($this->duration);
        
        return $endTime;
    }
}