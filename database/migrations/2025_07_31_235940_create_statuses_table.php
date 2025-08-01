<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
<<<<<<< HEAD
            $table->string('_id')->primary();
=======
            $table->integer('_id')->primary()->autoIncrement();
>>>>>>> 82fd9fd4d623a7ab665b1d6b02e6b51a0e6984ee
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
            $table->string('nombre');
            $table->text('descripcion');
            $table->string('grupo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('statuses');
    }
};
