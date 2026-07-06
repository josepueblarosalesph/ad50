<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->decimal('precio_uf', 8, 2)->nullable()->after('precio_clp');
            $table->string('recomendacion')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn(['precio_uf', 'recomendacion']);
        });
    }
};
