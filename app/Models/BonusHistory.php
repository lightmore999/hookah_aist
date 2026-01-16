<?php

// app/Models/BonusHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusHistory extends Model
{
    use HasFactory;

    protected $table = 'bonus_history';
    
    protected $fillable = [
        'client_id', // вместо user_id
        'amount',
        'operation_type',
        'balance_after',
        'reason',
        'sale_id'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];
    
    /**
     * Клиент, к которому относится запись
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
    /**
     * Продажа, связанная с операцией (если есть)
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}