<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->string('_id')->primary();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('tipo_usuario');
            $table->string('negocios');
            $table->text('nota')->nullable();
            $table->date('fecha');
            $table->integer('estados');
            $table->string('servicios');
            $table->date('fecha_fin');
            $table->integer('tiempo_estimado');
            $table->text('descripcion_cancel')->nullable();

            // Foreign keys
            $table->foreign('tipo_usuario')->references('_id')->on('categories')->onDelete('no action')->onUpdate('no action');
            $table->foreign('negocios')->references('_id')->on('businesses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('estados')->references('_id')->on('statuses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('servicios')->references('_id')->on('services')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
