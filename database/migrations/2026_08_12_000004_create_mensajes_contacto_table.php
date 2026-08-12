<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mensajes que postulantes y empresas envían a la administración.
 *
 * La bandeja es la fuente de verdad: se guarda en la base y se lee desde el panel de
 * admin, sin depender del correo. Un aviso por email puede sumarse encima, pero si el
 * envío falla el mensaje sigue aquí.
 *
 * `nombre` y `email` se copian al enviar en vez de leerse siempre del usuario: si esa
 * cuenta se elimina (o cambia de correo), la administración necesita saber quién
 * escribió y a dónde responder. Por eso `user_id` se anula en vez de arrastrar la fila.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes_contacto', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo', 30);
            $table->string('nombre', 120);
            $table->string('email', 255);
            $table->text('mensaje');
            $table->string('estado', 20)->default('nuevo');
            $table->timestamp('respondido_at')->nullable();
            $table->timestamps();

            // La bandeja se ordena por fecha y se filtra por estado y motivo.
            $table->index(['estado', 'created_at']);
            $table->index('motivo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_contacto');
    }
};
