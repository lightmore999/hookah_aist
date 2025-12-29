<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Employee extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'position',
        'social_network',
        'phone',
        'notes',
        'hookah_percentage',
        'hookah_rate',
        'shift_rate',
        'hourly_rate',
        'inn',
        'tips_link',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'hookah_percentage' => 'decimal:2',
        'hookah_rate' => 'decimal:2',
        'shift_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    /**
     * Автоматическое хеширование пароля при установке.
     *
     * @param  string  $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
    
    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'shift_user', 'user_id', 'shift_id')
                    ->withPivot(['start_time', 'end_time']) // ТОЛЬКО start_time и end_time
                    ->withTimestamps()
                    ->orderBy('date', 'desc');
    }

    /**
     * Связи через промежуточную таблицу
     */
    public function shiftUsers()
    {
        return $this->hasMany(ShiftUser::class, 'user_id');
    }

    /**
     * Получить статистику по сменам
     */
    public function getShiftStats()
    {
        $totalHours = $this->shiftUsers->reduce(function ($total, $shiftUser) {
            return $total + $shiftUser->hours_worked;
        }, 0);

        return [
            'total_shifts' => $this->shifts()->count(),
            'total_hours' => $totalHours,
        ];
    }
}