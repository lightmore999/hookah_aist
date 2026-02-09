<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    // Константы ролей
    const ROLE_ADMIN = 'admin';
    const ROLE_EMPLOYEE = 'employee';
    
    /**
     * Доступные роли
     */
    public static $roles = [
        self::ROLE_ADMIN => 'Администратор',
        self::ROLE_EMPLOYEE => 'Сотрудник',
    ];

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'shift_salary' => 'decimal:2',
        'revenue_percentage' => 'decimal:2',
    ];

    // Остальные методы отношений (fines, shifts и т.д.) остаются как были
    
    /**
     * Проверяет, является ли пользователь сотрудником
     */
    public function isEmployee()
    {
        return $this->role === self::ROLE_EMPLOYEE;
    }

    /**
     * Проверяет, является ли пользователь администратором
     */
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Scope для получения только сотрудников
     */
    public function scopeEmployees($query)
    {
        return $query->where('role', self::ROLE_EMPLOYEE);
    }

    /**
     * Scope для получения только администраторов
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Получить название роли
     */
    public function getRoleNameAttribute()
    {
        return self::$roles[$this->role] ?? $this->role;
    }
}