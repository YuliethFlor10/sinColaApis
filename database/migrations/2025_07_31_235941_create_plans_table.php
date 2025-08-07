<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id('_id');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nombre');
            $table->json('caracteristicas');
            $table->integer('descuentos');
            $table->unsignedBigInteger('estados');

            // Foreign keys
            $table->foreign('estados')->references('_id')->on('statuses')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('plans');
    }
};
