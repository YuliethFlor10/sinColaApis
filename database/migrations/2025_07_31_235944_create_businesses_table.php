<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->string('nit', 20)->unique();
            $table->string('nombre', 150);
            $table->text('direccion')->nullable();
            $table->string('telefono', 20)->nullable();

            $table->unsignedBigInteger('estados_id');
            $table->unsignedBigInteger('tipo_servicio_id');
            $table->unsignedBigInteger('planes_id');

            // Foreign Keys
            $table->foreign('estados_id')->references('id')->on('statuses');
            $table->foreign('tipo_servicio_id')->references('id')->on('categories');
            $table->foreign('planes_id')->references('id')->on('plans');
        });
    }

    public function down(): void {
        Schema::dropIfExists('businesses');
    }
};
