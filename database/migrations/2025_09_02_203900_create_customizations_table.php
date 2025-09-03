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

            // === BRANDING VISIBLE AL CLIENTE ===
            $table->string('nombre_comercial', 200)->nullable()->comment('Ej: MK Nails Salon - se muestra al cliente');
            $table->string('eslogan', 300)->nullable()->comment('Ej: Las mejores uñas pensando en ti...');
            $table->text('descripcion_negocio')->nullable()->comment('Descripción que ve el cliente');

            // === COLORES DEL TEMA ===
            $table->string('color_primario', 7)->default('#e91e63')->comment('Color principal (rosado actual)');
            $table->string('color_secundario', 7)->default('#c2185b')->comment('Color hover/secundario');
            $table->string('color_fondo_izquierdo', 7)->default('#f8d7da')->comment('Fondo gradiente izquierdo');
            $table->string('color_fondo_derecho', 7)->default('#d1477a')->comment('Fondo gradiente derecho');
            $table->string('color_texto_principal', 7)->default('#333333')->comment('Color texto principal');
            $table->string('color_texto_secundario', 7)->default('#666666')->comment('Color texto secundario');

            // === ARCHIVOS MULTIMEDIA ===
            $table->string('logo_principal', 500)->nullable()->comment('Logo grande del lado izquierdo');
            $table->string('logo_pequeno', 500)->nullable()->comment('Logo pequeño del header derecho');
            $table->string('favicon', 500)->nullable()->comment('Icono de la pestaña');

            // === CONFIGURACIÓN DE CITAS ===
            $table->integer('duracion_slot_minutos')->default(30)->comment('Duración de cada slot de tiempo');
            $table->integer('anticipacion_minima_horas')->default(2)->comment('Horas mínimas para agendar');
            $table->time('horario_atencion_inicio')->default('09:00')->comment('Hora inicio atención');
            $table->time('horario_atencion_fin')->default('18:00')->comment('Hora fin atención');
            $table->string('dias_atencion', 20)->default('L,M,M,J,V,S')->comment('Días de atención separados por coma');
            $table->integer('maximo_citas_dia')->default(20);

            // === TEXTOS PERSONALIZABLES ===
            $table->string('titulo_principal', 100)->default('¡Agenda SinCola!');
            $table->string('subtitulo_formulario', 200)->default('Por favor ingresa los siguientes datos para realizar tu reserva');
            $table->text('mensaje_bienvenida')->nullable()->comment('Mensaje del lado izquierdo');
            $table->text('mensaje_confirmacion')->nullable()->comment('Mensaje del modal de confirmación');
            $table->string('texto_seguir_redes', 100)->default('Síguenos en nuestras redes sociales');

            // === REDES SOCIALES ===
            $table->string('facebook_url', 500)->nullable();
            $table->string('instagram_url', 500)->nullable();
            $table->string('whatsapp_numero', 20)->nullable()->comment('Número con código país: +573001234567');
            $table->boolean('mostrar_redes_sociales')->default(true);

            // === MÉTODOS DE PAGO ===
            $table->boolean('acepta_efectivo')->default(true);
            $table->boolean('acepta_tarjeta')->default(true);
            $table->boolean('acepta_nequi')->default(true);
            $table->boolean('acepta_transferencia')->default(false);
            $table->string('texto_metodos_pago', 200)->default('Métodos de pago aceptados por');

            // === CONFIGURACIONES ADICIONALES ===
            $table->boolean('mostrar_precios_publicos')->default(true)->comment('Si se muestran precios antes de agendar');
            $table->boolean('requiere_confirmacion_email')->default(true);
            $table->boolean('requiere_confirmacion_telefono')->default(false);
            $table->boolean('permite_cancelacion_cliente')->default(true);
            $table->integer('horas_limite_cancelacion')->default(24);

            // === CONFIGURACIÓN EXTRA ===
            $table->text('configuracion_extra')->nullable()->comment('JSON para configuraciones específicas');

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
