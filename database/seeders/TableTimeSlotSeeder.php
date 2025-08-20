<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableTimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = \App\Models\Table::all();
        $timeSlots = \App\Models\TimeSlot::all();
        
        foreach ($tables as $table) {
            foreach ($timeSlots as $timeSlot) {
                $table->timeSlots()->attach($timeSlot->id, [
                    'is_disabled' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
