<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expenditure extends Model
{
    use HasFactory;

    protected $fillable = [
        'expenditure_type_id',
        'name',
        'cost',
        'payment_method',
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
}