<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsOperations;

class Expenditure extends Model
{
    use HasFactory, LogsOperations;

    protected $fillable = [
        'expenditure_type_id',
        'payment_method_id', // Заменяем payment_method на payment_method_id
        'name',
        'cost',
        'comment',
        'expenditure_date',
        'is_hidden_admin',
        'is_monthly_expense'
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'expenditure_date' => 'datetime',
        'is_hidden_admin' => 'boolean',
        'is_monthly_expense' => 'boolean'
    ];

    public function expenditureType()
    {
        return $this->belongsTo(ExpenditureType::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'IDPaymentMethod');
    }

    /**
     * Получить тип платежа текстом (аксессор для обратной совместимости)
     */
    public function getPaymentMethodTextAttribute(): string
    {
        return $this->paymentMethod ? $this->paymentMethod->Name : 'Не указано';
    }

    /**
     * Получить отформатированную дату
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->expenditure_date->format('d.m.Y H:i');
    }
}