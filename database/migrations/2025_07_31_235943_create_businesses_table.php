<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id('_id');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nit');
            $table->string('nombre');
            $table->string('direccion');
            $table->string('telefono');
            $table->unsignedBigInteger('estados');
            $table->unsignedBigInteger('tipo_servicio');
            $table->unsignedBigInteger('planes');

            // Foreign keys
            $table->foreign('estados')->references('_id')->on('statuses')->onDelete('no action')->onUpdate('no action');
           // $table->foreign('tipo_servicio')->references('_id')->on('categories')->onDelete('no action')->onUpdate('no action');
           // $table->foreign('planes')->references('_id')->on('plans')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('businesses');
    }
};
