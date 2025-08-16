<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->unsignedBigInteger('usuarios_id');
            $table->unsignedBigInteger('negocios_id');
            $table->unsignedBigInteger('servicios_id');
            $table->unsignedBigInteger('estados_id');

            $table->text('nota')->nullable();
            $table->datetime('fecha');
            $table->datetime('fecha_fin');
            $table->integer('tiempo_estimado')->nullable();
            $table->text('descripcion_cancel')->nullable();

            // Foreign Keys
            $table->foreign('usuarios_id')->references('id')->on('users')->onUpdate('cascade');
            $table->foreign('negocios_id')->references('id')->on('businesses')->onUpdate('cascade');
            $table->foreign('servicios_id')->references('id')->on('services')->onUpdate('cascade');
            $table->foreign('estados_id')->references('id')->on('statuses')->onUpdate('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('appointments');
    }
};

