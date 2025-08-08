<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nombre');
            $table->json('horarios');
            $table->boolean('activo');
            $table->unsignedBigInteger('negocios_id');
            $table->unsignedBigInteger('usuarios_id');

            // Foreign keys
            $table->foreign('negocios_id')->references('id')->on('businesses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('usuarios_id')->references('id')->on('users')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('agendas');
    }
};
