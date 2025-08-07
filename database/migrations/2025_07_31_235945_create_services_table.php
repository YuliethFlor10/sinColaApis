<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('abreviatura');
            $table->string('nombre');
            $table->text('descripcion');
            $table->integer('tiempo_estimado');
            $table->unsignedBigInteger('tipos_id');
            $table->unsignedBigInteger('estados_id');
            $table->unsignedBigInteger('negocios_id');
            $table->decimal('precio', 10, 2);

            // Foreign keys
            $table->foreign('tipos_id')->references('id')->on('categories')->onDelete('no action')->onUpdate('no action');
            $table->foreign('estados_id')->references('id')->on('statuses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('negocios_id')->references('id')->on('businesses')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
};
