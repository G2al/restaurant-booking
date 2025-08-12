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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Nome/numero tavolo (es: "Tavolo 1", "A1")
            $table->integer('capacity');      // Posti disponibili (es: 4, 6, 8)
            $table->boolean('is_active')->default(true); // Tavolo attivo/disattivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
