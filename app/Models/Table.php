<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

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
        'status',
    ];

    protected $casts = [
        'booking_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

}
