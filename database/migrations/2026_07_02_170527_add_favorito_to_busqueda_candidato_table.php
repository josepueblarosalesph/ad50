<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('busqueda_candidato', function (Blueprint $table) {
            $table->boolean('favorito')->default(false)->after('estado_match');
            $table->index(['busqueda_id', 'favorito']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('busqueda_candidato', function (Blueprint $table) {
            $table->dropIndex(['busqueda_id', 'favorito']);
            $table->dropColumn('favorito');
        });
    }
};
