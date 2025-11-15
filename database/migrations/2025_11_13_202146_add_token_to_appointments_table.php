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
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('confirmation_token', 64)->nullable()->after('descripcion_cancel');
            $table->timestamp('token_expires_at')->nullable()->after('confirmation_token');
            $table->index('confirmation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['confirmation_token']);
            $table->dropColumn(['confirmation_token', 'token_expires_at']);
        });
    }
};
