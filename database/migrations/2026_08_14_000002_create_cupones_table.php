<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupones', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->unique();          // siempre en mayúsculas (Cupon::normalizarCodigo)
            $t->string('descripcion')->nullable();   // para qué se creó: campaña, convenio, piloto
            $t->string('tipo');                      // porcentaje | monto
            $t->unsignedInteger('valor');            // 1..100 si es porcentaje; CLP si es monto
            // NULL = sirve para cualquier plan de empresa.
            $t->foreignId('plan_id')->nullable()->constrained('planes')->nullOnDelete();
            $t->unsignedInteger('max_usos')->nullable(); // NULL = sin tope
            $t->unsignedInteger('usos')->default(0);     // solo cobros confirmados
            $t->boolean('uso_unico_por_empresa')->default(true);
            $t->date('vigente_desde')->nullable();
            $t->date('vigente_hasta')->nullable();
            $t->boolean('activo')->default(true);
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::table('pagos', function (Blueprint $t) {
            // Qué cupón se usó y cuánto rebajó. `amount` sigue siendo lo que se cobra de
            // verdad (lo que se le manda a Flow), así que el precio de lista se reconstruye
            // como amount + descuento y ningún cálculo viejo cambia de significado.
            $t->foreignId('cupon_id')->nullable()->after('plan_id')->constrained('cupones')->nullOnDelete();
            $t->unsignedInteger('descuento')->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $t) {
            $t->dropConstrainedForeignId('cupon_id');
            $t->dropColumn('descuento');
        });

        Schema::dropIfExists('cupones');
    }
};
