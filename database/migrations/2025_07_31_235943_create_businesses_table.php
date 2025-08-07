<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nit');
            $table->string('nombre');
            $table->string('direccion');
            $table->string('telefono');
            $table->unsignedBigInteger('estados_id');
            $table->unsignedBigInteger('tipo_servicio_id');
            $table->unsignedBigInteger('planes_id');

            // Foreign keys
            $table->foreign('estados_id')->references('id')->on('statuses')->onDelete('no action')->onUpdate('no action');
           // $table->foreign('tipo_servicio_id')->references('id')->on('categories')->onDelete('no action')->onUpdate('no action');
           // $table->foreign('planes_id')->references('id')->on('plans')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('businesses');
    }
};
