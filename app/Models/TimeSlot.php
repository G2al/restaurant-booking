<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'time',
        'is_active'
    ];

    protected $casts = [
        'time' => 'datetime:H:i',
        'is_active' => 'boolean'
    ];

    // Relazione: uno slot ha molte prenotazioni
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function tables()
    {
        return $this->belongsToMany(Table::class, 'table_time_slots')
                    ->withPivot('is_disabled')
                    ->withTimestamps();
    }
}