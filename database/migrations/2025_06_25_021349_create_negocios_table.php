<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::create('negocios', function (Blueprint $table) {
    $table->id('id_negocio');
    $table->string('nombre_negocio');
    $table->string('direccion_negocio');
    $table->string('ciudad');
    $table->string('calle');
    $table->string('carrera');
    $table->string('tipo_negocio');
    $table->string('horario');
    $table->string('logo')->nullable();
    $table->string('telefono');
    $table->timestamps();
});

