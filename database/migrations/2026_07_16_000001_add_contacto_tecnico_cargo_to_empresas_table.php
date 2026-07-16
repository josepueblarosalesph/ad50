<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->string('contacto_tecnico_cargo')->nullable()->after('contacto_tecnico_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table): void {
            $table->dropColumn('contacto_tecnico_cargo');
        });
    }
};
