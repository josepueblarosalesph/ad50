<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $table) {
            $table->unsignedTinyInteger('onboarding_paso')->default(1)->after('completitud');
            $table->boolean('onboarding_completado')->default(true)->after('onboarding_paso');
        });
    }

    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $table) {
            $table->dropColumn(['onboarding_paso', 'onboarding_completado']);
        });
    }
};
