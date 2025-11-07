<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void 
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            
            // Timestamps personalizados
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            
            // Foreign Keys
            $table->unsignedBigInteger('usuarios_id');
            $table->unsignedBigInteger('negocios_id');
            $table->unsignedBigInteger('servicios_id');
            $table->unsignedBigInteger('estados_id')->default(1); // Pendiente por defecto
            
            // Datos de la cita
            $table->dateTime('fecha'); // Fecha y hora de inicio
            $table->dateTime('fecha_fin')->nullable(); // Fecha y hora de fin
            $table->integer('tiempo_estimado')->default(60); // En minutos
            
            // Notas
            $table->text('nota')->nullable();
            $table->text('descripcion_cancel')->nullable();
            
            // Relaciones
            $table->foreign('usuarios_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('negocios_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('servicios_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('estados_id')->references('id')->on('statuses');
            
            // Índices para optimizar consultas
            $table->index(['negocios_id', 'fecha']);
            $table->index(['usuarios_id', 'estados_id']);
        });
    }

    public function down(): void 
    {
        Schema::dropIfExists('appointments');
    }
};