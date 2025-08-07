<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nombre');
            $table->string('abreviatura');
            $table->text('descripcion');
            $table->string('grupo');
            $table->unsignedBigInteger('negocios_id');
            $table->unsignedBigInteger('estados_id');

            // Foreign keys
           // $table->foreign('negocios')->references('negocios_id')->on('businesses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('estados')->references('estados_id')->on('statuses')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
