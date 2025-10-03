<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customizations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // Relación con businesses (uno a uno)
            $table->unsignedBigInteger('negocios_id')->unique();

            // === INFORMACIÓN DEL NEGOCIO ===
            $table->string('nombre_comercial', 200)->nullable()->comment('Nombre comercial del negocio');
            $table->string('eslogan', 300)->nullable()->comment('Eslogan del negocio');
            $table->text('descripcion_negocio')->nullable()->comment('Descripción del negocio');

            // === REDES SOCIALES ===
            $table->string('facebook_url', 500)->nullable()->comment('URL de Facebook');
            $table->string('instagram_url', 500)->nullable()->comment('URL de Instagram');
            $table->string('whatsapp_numero', 20)->nullable()->comment('Número de WhatsApp con código país');
            $table->string('texto_seguir_redes', 100)->default('Síguenos en nuestras redes sociales')->comment('Texto para seguir redes sociales');

            // === MÉTODOS DE PAGO ===
            $table->boolean('acepta_efectivo')->default(true)->comment('Acepta pagos en efectivo');
            $table->boolean('acepta_tarjeta')->default(true)->comment('Acepta pagos con tarjeta');
            $table->boolean('acepta_nequi')->default(false)->comment('Acepta pagos con Nequi');
            $table->boolean('acepta_transferencia')->default(false)->comment('Acepta transferencias bancarias');
            $table->string('texto_metodos_pago', 200)->default('Métodos de pago aceptados')->comment('Texto descriptivo de métodos de pago');

            // === ARCHIVOS Y COLORES ===
            $table->string('logo_empresa', 500)->nullable()->comment('Logo de la empresa');
            $table->string('color_fondo_branding', 7)->default('#f8d7da')->comment('Color de fondo del branding');
            $table->string('color_letra_branding', 7)->default('#333333')->comment('Color de letra del branding');

            // Índices
            $table->index('negocios_id');

            // Clave foránea
            $table->foreign('negocios_id')->references('id')->on('businesses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customizations');
    }
};
