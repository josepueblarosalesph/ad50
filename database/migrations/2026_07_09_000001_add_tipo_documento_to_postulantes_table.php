<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $t) {
            $t->string('tipo_documento')->default('rut')->after('rut');
        });
    }

    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $t) {
            $t->dropColumn('tipo_documento');
        });
    }
};
