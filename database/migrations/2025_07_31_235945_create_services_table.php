<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id('_id');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('abreviatura');
            $table->string('nombre');
            $table->text('descripcion');
            $table->integer('tiempo_estimado');
            $table->unsignedBigInteger('tipos');
            $table->unsignedBigInteger('estados');
            $table->unsignedBigInteger('negocios');
            $table->decimal('precio', 10, 2);

            // Foreign keys
            $table->foreign('tipos')->references('_id')->on('categories')->onDelete('no action')->onUpdate('no action');
            $table->foreign('estados')->references('_id')->on('statuses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('negocios')->references('_id')->on('businesses')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
};