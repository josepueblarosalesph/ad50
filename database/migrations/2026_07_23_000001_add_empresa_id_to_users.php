<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // Vincula un usuario a la empresa a la que pertenece. El contacto
            // principal (dueño) sigue identificándose por empresas.user_id;
            // los usuarios adicionales del equipo comparten esta empresa_id.
            $t->foreignId('empresa_id')->nullable()->after('role')
                ->constrained('empresas')->nullOnDelete();
        });

        // Backfill: cada contacto principal existente apunta a su propia empresa.
        DB::statement('UPDATE users SET empresa_id = empresas.id FROM empresas WHERE empresas.user_id = users.id');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('empresa_id');
        });
    }
};
