<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->string('nombres', 30);
            $table->string('apellidos', 30);
            $table->string('email', 100)->unique();
            $table->date('nacimiento')->nullable();
            $table->enum('genero', ['M', 'F', 'O'])->default('O');
            $table->string('clave', 255);
            $table->rememberToken();
            $table->unsignedBigInteger('tipo_identificacion_id');
            $table->string('identificacion', 20);
            $table->string('celular', 20)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->text('direccion')->nullable();
            $table->boolean('terminos_condiciones')->default(false);
            $table->unsignedBigInteger('estados_id');
            $table->unsignedBigInteger('roles_id');
            $table->unsignedBigInteger('negocios_id')->nullable();

            // Foreign Keys
            $table->foreign('tipo_identificacion_id')->references('id')->on('categories');
            $table->foreign('estados_id')->references('id')->on('statuses');
            $table->foreign('roles_id')->references('id')->on('roles');
            $table->foreign('negocios_id')->references('id')->on('businesses')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};
