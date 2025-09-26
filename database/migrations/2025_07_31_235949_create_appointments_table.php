<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Información personal del cliente
            $table->string('tipo_documento', 20); // CC, TI, CE, etc.
            $table->string('numero_documento', 50);
            $table->string('nombre', 255);
            $table->string('email', 255);
            $table->date('fecha_nacimiento');
            $table->string('numero_telefono', 20);

            // Información del servicio y cita
            $table->string('tipo_cita', 100);
            $table->string('personal_servicio', 255); // Personal que realiza el servicio
            $table->date('fecha_cita');
            $table->time('hora_cita');
            $table->datetime('fecha_hora_completa'); // Combinación de fecha y hora para consultas más fáciles
            
            // Campos adicionales del sistema
            $table->unsignedBigInteger('usuarios_id')->nullable(); // Si el cliente está registrado como usuario
            $table->unsignedBigInteger('negocios_id');
            $table->unsignedBigInteger('servicios_id')->nullable(); // Referencia al servicio específico si existe tabla services
            $table->unsignedBigInteger('estados_id')->default(1); // Estado por defecto: pendiente/programada
            
            $table->text('nota')->nullable();
            $table->integer('tiempo_estimado')->nullable(); // En minutos
            $table->text('descripcion_cancel')->nullable();
            $table->datetime('fecha_fin')->nullable(); // Calculada automáticamente

            // Índices para mejor rendimiento
            $table->index(['fecha_cita', 'hora_cita']);
            $table->index(['numero_documento']);
            $table->index(['email']);
            $table->index(['negocios_id', 'fecha_cita']);

            // Foreign Keys
            $table->foreign('usuarios_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('negocios_id')->references('id')->on('businesses')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('servicios_id')->references('id')->on('services')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('estados_id')->references('id')->on('statuses')->onUpdate('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('appointments');
    }
};