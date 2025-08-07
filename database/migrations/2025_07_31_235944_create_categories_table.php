<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id('_id');
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nombre');
            $table->string('abreviatura');
            $table->text('descripcion');
            $table->string('grupo');
            $table->unsignedBigInteger('negocios');
            $table->unsignedBigInteger('estados');

            // Foreign keys
           // $table->foreign('negocios')->references('_id')->on('businesses')->onDelete('no action')->onUpdate('no action');
            $table->foreign('estados')->references('_id')->on('statuses')->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};