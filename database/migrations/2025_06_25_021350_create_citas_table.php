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
     Schema::create('citas', function (Blueprint $table) {
    $table->id('id_cita');
    $table->foreignId('usuario_id')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
    $table->foreignId('servicio_id')->constrained('servicios', 'id_servicio')->onDelete('cascade');
    $table->date('fecha');
    $table->time('hora');
    $table->integer('duracion_cita');
    $table->string('estado')->default('pendiente');
    $table->text('observaciones')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
