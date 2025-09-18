<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacities',   // JSON con array di posti
        'is_active',
        'opening_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'opening_days' => 'array',
        'capacities' => 'array', // sempre array
    ];

    // 🔹 Mutator: forza sempre capacities come array di interi
    public function setCapacitiesAttribute($value)
    {
        if (is_string($value)) {
            // Se è una stringa tipo "3,5,6" → array
            $value = explode(',', $value);
        }

        if (is_array($value)) {
            // Converte tutti gli elementi in interi
            $value = array_map('intval', $value);
        }

        $this->attributes['capacities'] = json_encode($value);
    }

    // Relazione: un tavolo ha molte prenotazioni
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isOpenOnDay($dayOfWeek)
    {
        return in_array((string)$dayOfWeek, $this->opening_days);
    }

    public function timeSlots()
    {
        return $this->belongsToMany(TimeSlot::class, 'table_time_slots')
                    ->withPivot('is_disabled')
                    ->withTimestamps();
    }
}
