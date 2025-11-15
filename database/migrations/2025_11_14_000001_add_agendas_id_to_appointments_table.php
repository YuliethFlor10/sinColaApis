<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Agregar FK de agenda si no existe
            if (!Schema::hasColumn('appointments', 'agendas_id')) {
                $table->unsignedBigInteger('agendas_id')->nullable()->after('servicios_id');
                $table->foreign('agendas_id')->references('id')->on('agendas')->onDelete('set null');
                $table->index('agendas_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'agendas_id')) {
                $table->dropForeign(['agendas_id']);
                $table->dropColumn('agendas_id');
            }
        });
    }
};
