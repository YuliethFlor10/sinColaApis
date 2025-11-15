<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cambiar tipo_identificacion_id para que sea nullable por compatibilidad
            // O tener un valor por defecto si la tabla de categories existe
            if (Schema::hasColumn('users', 'tipo_identificacion_id')) {
                $table->unsignedBigInteger('tipo_identificacion_id')->default(1)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tipo_identificacion_id')) {
                $table->unsignedBigInteger('tipo_identificacion_id')->change();
            }
        });
    }
};
