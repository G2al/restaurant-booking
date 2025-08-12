<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'date',
        'time_slot_id',
        'guests_count',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'special_requests'
    ];

    protected $casts = [
        'date' => 'date',
        'guests_count' => 'integer'
    ];

    // Relazione: una prenotazione appartiene a un tavolo
    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    // Relazione: una prenotazione appartiene a uno slot
    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }
}