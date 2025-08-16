<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->string('abreviatura', 10)->nullable();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->integer('tiempo_estimado')->nullable();
            $table->decimal('precio', 10, 2)->nullable();

            $table->unsignedBigInteger('tipos_id');
            $table->unsignedBigInteger('estados_id');
            $table->unsignedBigInteger('negocios_id');

            // Foreign Keys
            $table->foreign('tipos_id')->references('id')->on('categories');
            $table->foreign('estados_id')->references('id')->on('statuses');
            $table->foreign('negocios_id')->references('id')->on('businesses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('services');
    }
};
