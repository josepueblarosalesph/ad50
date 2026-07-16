<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('busqueda_candidato', function (Blueprint $table): void {
            $table->text('nota')->nullable()->after('favorito');
        });
    }

    public function down(): void
    {
        Schema::table('busqueda_candidato', function (Blueprint $table): void {
            $table->dropColumn('nota');
        });
    }
};
