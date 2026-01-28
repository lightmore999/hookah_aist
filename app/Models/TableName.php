<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TableName extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Scope для активных столов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для сортировки по порядку
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Получить все активные столы в нужном порядке
     */
    public static function getActiveTables()
    {
        return self::active()->ordered()->get();
    }

    /**
     * Получить массив имен активных столов
     */
    public static function getActiveTableNames()
    {
        return self::active()->ordered()->pluck('name')->toArray();
    }

    /**
     * Проверить, существует ли стол с таким именем
     */
    public static function existsByName($name)
    {
        return self::where('name', $name)->exists();
    }

    /**
     * Найти стол по имени
     */
    public static function findByName($name)
    {
        return self::where('name', $name)->first();
    }
}