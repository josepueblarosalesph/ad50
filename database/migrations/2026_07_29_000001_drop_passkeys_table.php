<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se retiró el inicio de sesión con passkeys, así que la tabla deja de usarse.
 *
 * La migración que la creaba también se eliminó: en una instalación nueva esta
 * migración no encuentra nada que borrar (`dropIfExists`) y en las existentes limpia
 * la tabla junto con las credenciales guardadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('passkeys');
    }

    public function down(): void
    {
        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('credential_id')->unique();
            $table->json('credential');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }
};
