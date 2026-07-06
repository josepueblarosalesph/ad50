<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $table) {
            $table->string('region_interes')->nullable()->after('ciudad');
            $table->string('region_interes_2')->nullable()->after('region_interes');
            $table->string('region_interes_3')->nullable()->after('region_interes_2');
            $table->string('modalidad_trabajo', 40)->nullable()->after('region_interes_3');
        });
    }

    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $table) {
            $table->dropColumn([
                'region_interes',
                'region_interes_2',
                'region_interes_3',
                'modalidad_trabajo',
            ]);
        });
    }
};
