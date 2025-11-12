<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void 
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Datos del cliente almacenados directamente en la cita
            $table->string('cliente_nombre', 255)->nullable()->after('nota');
            $table->string('cliente_email', 255)->nullable()->after('cliente_nombre');
            $table->string('cliente_tipo_doc', 10)->nullable()->after('cliente_email');
            $table->string('cliente_num_doc', 50)->nullable()->after('cliente_tipo_doc');
            $table->date('cliente_fecha_nac')->nullable()->after('cliente_num_doc');
            $table->string('cliente_telefono', 20)->nullable()->after('cliente_fecha_nac');
            
            // Datos del servicio
            $table->string('tipo_servicio', 100)->nullable()->after('cliente_telefono');
            $table->string('personal_asignado', 255)->nullable()->after('tipo_servicio');
        });
    }

    public function down(): void 
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'cliente_nombre',
                'cliente_email',
                'cliente_tipo_doc',
                'cliente_num_doc',
                'cliente_fecha_nac',
                'cliente_telefono',
                'tipo_servicio',
                'personal_asignado'
            ]);
        });
    }
};