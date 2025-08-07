<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('_id');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('email');
            $table->date('nacimiento');
            $table->integer('edad');
            $table->string('genero');
            $table->string('clave');
            $table->unsignedBigInteger('tipo_identificacion');
            $table->string('identificacion');
            $table->integer('celular');
            $table->string('telefono')->nullable();
            $table->string('direccion');
            $table->boolean('terminos_condiciones');
            $table->unsignedBigInteger('estados');
            $table->unsignedBigInteger('roles');
            $table->unsignedBigInteger('negocios')->nullable();
            $table->unsignedBigInteger('servicios')->nullable();

            // Foreign keys
            $table->foreign('tipo_identificacion')->references('_id')->on('categories')->onDelete('no action')->onUpdate('no action');
            $table->foreign('estados')->references('_id')->on('statuses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('roles')->references('_id')->on('roles')->onDelete('no action')->onUpdate('no action');
            $table->foreign('negocios')->references('_id')->on('businesses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('servicios')->references('_id')->on('services')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
