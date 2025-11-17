<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_user', function (Blueprint $table) {
            // Eliminar columnas de timestamps si existen
            if (Schema::hasColumn('service_user', 'created_at')) {
                $table->dropColumn(['created_at', 'updated_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_user', function (Blueprint $table) {
            $table->timestamps();
        });
    }
};
