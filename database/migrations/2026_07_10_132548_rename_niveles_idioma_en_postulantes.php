<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> */
    private const RENOMBRES = ['Medio' => 'Intermedio', 'Alto' => 'Avanzado'];

    public function up(): void
    {
        $this->renombrarNiveles(self::RENOMBRES);
    }

    public function down(): void
    {
        $this->renombrarNiveles(array_flip(self::RENOMBRES));
    }

    /** @param  array<string, string>  $mapa */
    private function renombrarNiveles(array $mapa): void
    {
        DB::table('postulantes')->whereNotNull('idiomas')->orderBy('id')->each(function (object $postulante) use ($mapa): void {
            $idiomas = json_decode((string) $postulante->idiomas, true);

            if (! is_array($idiomas) || $idiomas === []) {
                return;
            }

            $renombrados = collect($idiomas)
                ->map(fn (array $idioma): array => [
                    ...$idioma,
                    'nivel' => $mapa[$idioma['nivel'] ?? ''] ?? ($idioma['nivel'] ?? null),
                ])
                ->all();

            DB::table('postulantes')->where('id', $postulante->id)->update(['idiomas' => json_encode($renombrados)]);
        });
    }
};
