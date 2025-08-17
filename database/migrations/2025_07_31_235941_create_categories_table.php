<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Timestamps personalizados
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            // Campos específicos
            $table->string('nombre');
            $table->string('abreviatura')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('grupo')->nullable();

            // Relaciones
            $table->unsignedBigInteger('estados_id');

            // Foreign key
            $table->foreign('estados_id')
                  ->references('id')->on('statuses')
                  ->onUpdate('no action')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
