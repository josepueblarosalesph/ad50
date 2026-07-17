<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table): void {
            // Cantidad de perfiles que la empresa puede desbloquear con el plan (0 = sin cupo).
            $table->unsignedInteger('desbloqueos')->default(0)->after('precio_uf');
        });

        Schema::create('desbloqueos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('postulante_id')->constrained('postulantes')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['empresa_id', 'postulante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desbloqueos');

        Schema::table('planes', function (Blueprint $table): void {
            $table->dropColumn('desbloqueos');
        });
    }
};
