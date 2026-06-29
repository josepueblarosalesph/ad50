<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users (extiende el del starter kit; añadimos 'role')
        Schema::table('users', function (Blueprint $t) {
            $t->enum('role', ['postulante', 'empresa', 'admin'])->default('postulante')->after('email');
            $t->boolean('acepta_ley_21719')->default(false)->after('role');
        });

        Schema::create('planes', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->unique();           // postulante, empresa_basic, empresa_pro
            $t->string('nombre');
            $t->string('audiencia');                  // postulante | empresa
            $t->unsignedInteger('precio_clp');
            $t->string('periodo')->default('mensual');
            $t->json('features')->nullable();
            $t->boolean('destacado')->default(false);
            $t->timestamps();
        });

        Schema::create('postulantes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('telefono')->nullable();
            $t->string('ciudad')->nullable();
            $t->string('cargo_actual')->nullable();
            $t->string('industria')->nullable();
            $t->unsignedTinyInteger('anios_experiencia')->default(0);
            $t->unsignedTinyInteger('completitud')->default(0);   // 0..100
            $t->boolean('visible')->default(true);
            $t->date('suscripcion_hasta')->nullable();
            $t->timestamps();
        });

        Schema::create('empresas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('razon_social');
            $t->string('rut')->nullable();
            $t->string('rubro')->nullable();
            $t->foreignId('plan_id')->nullable()->constrained('planes')->nullOnDelete();
            $t->date('plan_hasta')->nullable();
            $t->timestamps();
        });

        Schema::create('busquedas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $t->string('titulo');
            $t->string('rubro_oculto')->nullable();   // lo que ve el postulante hasta ser contactado
            $t->json('criterios')->nullable();        // {cargo, industria, anios_min, ...}
            $t->enum('estado', ['activa', 'pausada', 'cerrada'])->default('activa');
            $t->timestamps();
        });

        Schema::create('busqueda_candidato', function (Blueprint $t) {
            $t->id();
            $t->foreignId('busqueda_id')->constrained()->cascadeOnDelete();
            $t->foreignId('postulante_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('match_score')->default(0);    // 0..100
            $t->unsignedTinyInteger('criterios_cumplidos')->default(0);
            $t->unsignedTinyInteger('criterios_totales')->default(0);
            $t->enum('estado_match', ['cumple', 'parcial'])->default('parcial');
            $t->timestamp('contactado_at')->nullable();
            $t->timestamps();
            $t->unique(['busqueda_id', 'postulante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('busqueda_candidato');
        Schema::dropIfExists('busquedas');
        Schema::dropIfExists('empresas');
        Schema::dropIfExists('postulantes');
        Schema::dropIfExists('planes');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['role', 'acepta_ley_21719']);
        });
    }
};
