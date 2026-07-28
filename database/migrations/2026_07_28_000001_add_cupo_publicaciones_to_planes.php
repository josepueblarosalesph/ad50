<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table): void {
            // Publicaciones que la empresa puede crear con el plan. NULL = ilimitadas.
            // A diferencia de `desbloqueos` (0 = sin cupo), aquí 0 significa que el plan
            // no permite publicar, por eso el "ilimitado" se representa con NULL.
            $table->unsignedInteger('publicaciones')->nullable()->after('desbloqueos');
        });
    }

    public function down(): void
    {
        Schema::table('planes', function (Blueprint $table): void {
            $table->dropColumn('publicaciones');
        });
    }
};
