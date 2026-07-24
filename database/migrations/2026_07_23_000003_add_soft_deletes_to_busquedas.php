<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('busquedas', function (Blueprint $t) {
            // Borrado lógico: al eliminar un proceso queda en "papelera" y puede
            // deshacerse; una tarea programada lo purga definitivamente tras 30 días.
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('busquedas', function (Blueprint $t) {
            $t->dropSoftDeletes();
        });
    }
};
