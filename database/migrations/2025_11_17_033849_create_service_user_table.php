<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('servicios_id');
            $table->unsignedBigInteger('usuarios_id');

            $table->foreign('servicios_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('usuarios_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
            $table->unique(['servicios_id', 'usuarios_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_user');
    }
};
