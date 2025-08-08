<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nombre');
            $table->json('caracteristicas');
            $table->integer('descuentos');
            $table->unsignedBigInteger('estados_id');

            // Foreign keys
            $table->foreign('estados_id')->references('id')->on('statuses')->onDelete('cascade')->onUpdate('cascade');;
        });
    }

    public function down()
    {
        Schema::dropIfExists('plans');
    }
};
