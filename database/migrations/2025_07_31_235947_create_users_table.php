<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('email');
            $table->date('nacimiento');
            $table->integer('edad');
            $table->string('genero');
            $table->string('clave');
            $table->unsignedBigInteger('tipo_identificacion_id');
            $table->string('identificacion');
            $table->integer('celular');
            $table->string('telefono')->nullable();
            $table->string('direccion');
            $table->boolean('terminos_condiciones');
            $table->unsignedBigInteger('estados_id');
            $table->unsignedBigInteger('roles_id');
            $table->unsignedBigInteger('negocios_id')->nullable();
            $table->unsignedBigInteger('servicios_id')->nullable();

            // Foreign keys
            $table->foreign('tipo_identificacion')->references('tipo_identificacion_id')->on('categories')->onDelete('no action')->onUpdate('no action');
            $table->foreign('estados')->references('estados_id')->on('statuses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('roles')->references('roles_id')->on('roles')->onDelete('no action')->onUpdate('no action');
            $table->foreign('negocios')->references('negocios_id')->on('businesses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('servicios')->references('servicios_id')->on('services')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
