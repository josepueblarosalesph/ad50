<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $t) {
            $t->json('regiones_interes')->nullable()->after('ciudad');
            $t->json('industrias_interes')->nullable()->after('regiones_interes');
        });

        DB::table('postulantes')->orderBy('id')->each(function (object $postulante): void {
            DB::table('postulantes')->where('id', $postulante->id)->update([
                'regiones_interes' => $this->comoLista([$postulante->region_interes, $postulante->region_interes_2, $postulante->region_interes_3]),
                'industrias_interes' => $this->comoLista([$postulante->industria, $postulante->industria_2, $postulante->industria_3]),
            ]);
        });

        Schema::table('postulantes', function (Blueprint $t) {
            $t->dropColumn(['region_interes', 'region_interes_2', 'region_interes_3', 'industria', 'industria_2', 'industria_3']);
        });
    }

    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $t) {
            $t->string('region_interes')->nullable();
            $t->string('region_interes_2')->nullable();
            $t->string('region_interes_3')->nullable();
            $t->string('industria')->nullable();
            $t->string('industria_2')->nullable();
            $t->string('industria_3')->nullable();
        });

        DB::table('postulantes')->orderBy('id')->each(function (object $postulante): void {
            $regiones = json_decode((string) $postulante->regiones_interes, true) ?: [];
            $industrias = json_decode((string) $postulante->industrias_interes, true) ?: [];

            DB::table('postulantes')->where('id', $postulante->id)->update([
                'region_interes' => $regiones[0] ?? null,
                'region_interes_2' => $regiones[1] ?? null,
                'region_interes_3' => $regiones[2] ?? null,
                'industria' => $industrias[0] ?? null,
                'industria_2' => $industrias[1] ?? null,
                'industria_3' => $industrias[2] ?? null,
            ]);
        });

        Schema::table('postulantes', function (Blueprint $t) {
            $t->dropColumn(['regiones_interes', 'industrias_interes']);
        });
    }

    /**
     * @param  array<int, ?string>  $valores
     */
    private function comoLista(array $valores): string
    {
        return json_encode(array_values(array_filter($valores)), JSON_UNESCAPED_UNICODE);
    }
};
