<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('busquedas')
            ->where('estado', 'cancelado_cliente')
            ->update(['estado' => 'cancelado']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
