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
        Schema::create('ingreso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auto_id')->constrained('auto')->onDelete('cascade');
            $table->foreignId('ruta_id')->constrained('ruta')->onDelete('cascade');
            $table->foreignId('turno_id')->constrained('turno')->onDelete('cascade');
            $table->string('serial')->unique();
            $table->string('servicio');
            $table->decimal('monto', 10, 2);
            $table->date('fecha'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
