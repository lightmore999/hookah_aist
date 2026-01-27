<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'position',
        'social_network',
        'phone',
        'notes',
        'shift_salary',
        'revenue_percentage',
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
        'shift_salary' => 'decimal:2',
        'revenue_percentage' => 'decimal:2',
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
    
    /**
     * Штрафы пользователя
     */
    public function fines()
    {
        return $this->hasMany(Fine::class);
    }

    /**
     * Смены пользователя (для сотрудников)
     */
    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'shift_user', 'user_id', 'shift_id')
                    // Убираем ->withPivot(['start_time', 'end_time'])
                    ->withTimestamps()
                    ->orderBy('date', 'desc');
    }

    /**
     * Связи через промежуточную таблицу shift_user
     */
    public function shiftUsers()
    {
        return $this->hasMany(ShiftUser::class, 'user_id');
    }

    /**
     * Получить статистику по сменам (для сотрудников)
     */
    public function getShiftStats()
    {
        // Проверяем, является ли пользователь сотрудником
        if ($this->role !== 'employee') {
            return [
                'total_shifts' => 0,
                'total_hours' => 0,
            ];
        }

        // Если нет start_time/end_time, то часы не считаем
        return [
            'total_shifts' => $this->shifts()->count(),
            'total_hours' => 0, // или убираем совсем, если не нужно
        ];
    }

    /**
     * Проверяет, является ли пользователь сотрудником
     */
    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    /**
     * Проверяет, является ли пользователь администратором
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Проверяет, является ли пользователь менеджером
     * (можно добавить другие роли позже)
     */
    public function isManager()
    {
        return $this->role === 'manager';
    }

    /**
     * Scope для получения только сотрудников
     */
    public function scopeEmployees($query)
    {
        return $query->where('role', 'employee');
    }

    /**
     * Scope для получения только администраторов
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }
}