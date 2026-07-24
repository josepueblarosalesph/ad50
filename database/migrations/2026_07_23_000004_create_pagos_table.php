<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained('planes');
            $t->string('commerce_order')->unique();   // nuestro identificador enviado a Flow
            $t->string('flow_token')->nullable()->index();
            $t->unsignedBigInteger('flow_order')->nullable();
            $t->unsignedInteger('amount');            // monto en CLP
            $t->string('currency')->default('CLP');
            $t->string('estado')->default('pendiente'); // pendiente | pagado | rechazado | anulado | error
            $t->timestamp('pagado_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
