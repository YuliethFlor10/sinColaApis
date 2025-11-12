<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ✅ Hacer que 'clave' acepte NULL para clientes sin contraseña
            $table->string('clave', 255)->nullable()->change();

            // ✅ Ajustar límites de nombres para coincidir con validación del controlador
            $table->string('nombres', 100)->change();
            $table->string('apellidos', 100)->change();

            // ✅ Ajustar límite de identificación para coincidir con validación
            $table->string('identificacion', 30)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revertir cambios si es necesario
            $table->string('clave', 255)->nullable(false)->change();
            $table->string('nombres', 30)->change();
            $table->string('apellidos', 30)->change();
            $table->string('identificacion', 20)->change();
        });
    }
};
