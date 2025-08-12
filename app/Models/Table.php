<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'is_active',
        'opening_days'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'opening_days' => 'array'
    ];

    // Relazione: un tavolo ha molte prenotazioni
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isOpenOnDay($dayOfWeek)
    {
        return in_array((string)$dayOfWeek, $this->opening_days);
    }
}