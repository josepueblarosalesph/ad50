<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $table): void {
            $table->json('habilidades')->nullable()->after('resumen_profesional');
        });
    }

    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $table): void {
            $table->dropColumn('habilidades');
        });
    }
};
