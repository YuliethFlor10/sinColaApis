<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->string('nombre', 100);
            $table->json('caracteristicas')->nullable();
            $table->integer('descuentos')->nullable();

            $table->unsignedBigInteger('estados_id');

            // Foreign key
            $table->foreign('estados_id')->references('id')->on('statuses')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('plans');
    }
};
