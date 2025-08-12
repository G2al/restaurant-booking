<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            $table->date('date');                    // Data prenotazione (2025-01-15)
            $table->foreignId('time_slot_id')->constrained('time_slots')->onDelete('cascade');
            $table->integer('guests_count');         // Numero persone
            $table->string('customer_name');         // Nome cliente
            $table->string('customer_email');        // Email cliente  
            $table->string('customer_phone');        // Telefono cliente
            $table->enum('status', ['confirmed', 'cancelled_by_restaurant', 'cancelled_by_customer'])
                ->default('confirmed');            // Status prenotazione
            $table->timestamps();
            
            // Indice unico: un tavolo può avere solo una prenotazione per data+slot
            $table->unique(['table_id', 'date', 'time_slot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
