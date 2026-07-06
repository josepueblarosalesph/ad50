<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('planes')->where('codigo', 'empresa_basic')->update(['nombre' => 'Básico']);
        DB::table('planes')->where('codigo', 'empresa_pro')->update(['nombre' => 'Profesional']);
    }

    public function down(): void
    {
        DB::table('planes')->where('codigo', 'empresa_basic')->update(['nombre' => 'Empresa · Básico']);
        DB::table('planes')->where('codigo', 'empresa_pro')->update(['nombre' => 'Empresa · Pro']);
    }
};
