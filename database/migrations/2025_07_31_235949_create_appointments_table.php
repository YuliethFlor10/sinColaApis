<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->unsignedBigInteger('tipo_usuario_id');
            $table->unsignedBigInteger('negocios_id');
            $table->text('nota')->nullable();
            $table->date('fecha');
            $table->unsignedBigInteger('estados_id');
            $table->unsignedBigInteger('servicios_id');
            $table->date('fecha_fin');
            $table->integer('tiempo_estimado');
            $table->text('descripcion_cancel')->nullable();

            // Foreign keys
            $table->foreign('tipo_usuario')->references('tipo_usuario_id')->on('categories')->onDelete('no action')->onUpdate('no action');
            $table->foreign('negocios')->references('negocios_id')->on('businesses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('estados')->references('estados_id')->on('statuses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('servicios')->references('servicios_id')->on('services')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
