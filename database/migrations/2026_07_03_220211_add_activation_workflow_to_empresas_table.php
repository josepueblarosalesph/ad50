<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('estado_activacion', 20)->default('inactiva')->index()->after('rubro');
            $table->string('contacto_principal_nombre')->nullable()->after('estado_activacion');
            $table->string('contacto_principal_cargo')->nullable()->after('contacto_principal_nombre');
            $table->string('contacto_principal_email')->nullable()->after('contacto_principal_cargo');
            $table->string('contacto_principal_telefono', 30)->nullable()->after('contacto_principal_email');
            $table->string('contacto_tecnico_nombre')->nullable()->after('contacto_principal_telefono');
            $table->string('contacto_tecnico_email')->nullable()->after('contacto_tecnico_nombre');
            $table->string('contacto_tecnico_telefono', 30)->nullable()->after('contacto_tecnico_email');
            $table->timestamp('datos_enviados_at')->nullable()->after('contacto_tecnico_telefono');
            $table->timestamp('activada_at')->nullable()->after('datos_enviados_at');
            $table->foreignId('activada_por')->nullable()->after('activada_at')->constrained('users')->nullOnDelete();
        });

        DB::table('empresas')->update([
            'estado_activacion' => 'activa',
            'activada_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropForeign(['activada_por']);
            $table->dropColumn([
                'estado_activacion',
                'contacto_principal_nombre',
                'contacto_principal_cargo',
                'contacto_principal_email',
                'contacto_principal_telefono',
                'contacto_tecnico_nombre',
                'contacto_tecnico_email',
                'contacto_tecnico_telefono',
                'datos_enviados_at',
                'activada_at',
                'activada_por',
            ]);
        });
    }
};
