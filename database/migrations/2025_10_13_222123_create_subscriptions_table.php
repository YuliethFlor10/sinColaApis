<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            // Relaciones (una suscripción pertenece a un usuario y un negocio)
            $table->unsignedBigInteger('usuarios_id');
            $table->unsignedBigInteger('negocios_id')->nullable();
            $table->unsignedBigInteger('planes_id');

            // Fechas de suscripción
            $table->date('fecha_inicio')->comment('Fecha de inicio de la suscripción');
            $table->date('fecha_fin')->comment('Fecha de vencimiento de la suscripción');

            // Estado
            $table->enum('estado', ['activa', 'cancelada', 'suspendida', 'vencida'])->default('activa');

            // Información de pago
            $table->decimal('precio_pagado', 10, 2)->comment('Precio pagado por la suscripción');
            $table->string('metodo_pago', 50)->nullable()->comment('Método de pago');
            $table->string('transaccion_id', 255)->nullable()->unique()->comment('ID de transacción del proveedor');

            // Información de uso (para planes limitados)
            $table->integer('notificaciones_usadas')->default(0);
            $table->integer('notificaciones_totales')->nullable()->comment('Null = ilimitadas, -1 = ilimitadas');

            // Control de cancelación
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->text('razon_cancelacion')->nullable();

            // Foreign Keys
            $table->foreign('usuarios_id')
                ->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('negocios_id')
                ->references('id')->on('businesses')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('planes_id')
                ->references('id')->on('plans')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            // Índices
            $table->index('usuarios_id');
            $table->index('negocios_id');
            $table->index('planes_id');
            $table->index('estado');
            $table->index('fecha_fin');
        });
    }

    public function down(): void {
        Schema::dropIfExists('subscriptions');
    }
};
