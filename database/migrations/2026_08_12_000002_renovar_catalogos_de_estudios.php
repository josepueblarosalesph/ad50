<?php

use App\Support\CatalogosProfesionales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nuevas listas de nivel de estudios y situación académica.
 *
 * Los dos catálogos se validan con Rule::in(), así que cambiar las opciones sin tocar lo
 * ya guardado dejaría fichas y publicaciones con valores inválidos: no fallarían al
 * leerse, pero sí en cuanto alguien volviera a guardar el formulario. Por eso aquí se
 * hacen las tres cosas juntas: se reescriben los términos administrables, se remapea lo
 * guardado y se descarta la caché de catálogos.
 *
 * Del mapeo, lo único que no es una simple renombrada:
 *  - los niveles escolares (básica, media, técnico medio) ya no existen como opción y
 *    caen en "Otro": la ficha ahora arranca en el título profesional;
 *  - "Postgrado", que era el cajón genérico, pasa a "Diplomado / Postítulo", el más
 *    cercano de la lista nueva sin ascender a nadie a magíster.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private const NIVELES = [
        'Básica' => 'Otro',
        'Media' => 'Otro',
        'Técnico Medio / Colegio Técnico' => 'Otro',
        'Técnico profesional superior' => 'CFT / Instituto Profesional',
        'Universitaria' => 'Título Profesional',
        'Diplomado' => 'Diplomado / Postítulo',
        'Postgrado' => 'Diplomado / Postítulo',
        'Magíster' => 'Magíster',
        'Doctorado' => 'Doctorado',
        'Otro' => 'Otro',
    ];

    /** @var array<string, string> */
    private const SITUACIONES = [
        'Titulado / Titulada' => 'Titulado/a',
        'Egresado' => 'Egresado/a',
        'Estudiando' => 'Estudiando',
        'Incompleto' => 'Incompleto',
    ];

    public function up(): void
    {
        $this->reescribirCatalogo('nivel_estudios', [
            'CFT / Instituto Profesional', 'Título Profesional', 'Diplomado / Postítulo',
            'Magíster', 'Especialidad Área Salud', 'Doctorado', 'Otro',
        ]);

        $this->reescribirCatalogo('situacion_estudios', [
            'Titulado/a', 'Egresado/a', 'Estudiando', 'Incompleto',
        ]);

        $this->remapearFichas(self::NIVELES, self::SITUACIONES);
        $this->remapearPublicaciones(self::NIVELES, self::SITUACIONES);

        CatalogosProfesionales::olvidar();
    }

    public function down(): void
    {
        $this->reescribirCatalogo('nivel_estudios', [
            'Básica', 'Media', 'Técnico Medio / Colegio Técnico',
            'Técnico profesional superior', 'Universitaria', 'Diplomado',
            'Postgrado', 'Magíster', 'Doctorado', 'Otro',
        ]);

        $this->reescribirCatalogo('situacion_estudios', [
            'Egresado', 'Titulado / Titulada', 'Estudiando', 'Incompleto',
        ]);

        // El mapeo de vuelta no puede ser exacto: varios valores viejos caían en el mismo
        // nuevo. Se devuelve el representante más común de cada uno.
        $this->remapearFichas(
            ['Título Profesional' => 'Universitaria', 'CFT / Instituto Profesional' => 'Técnico profesional superior', 'Diplomado / Postítulo' => 'Diplomado'],
            array_flip(self::SITUACIONES),
        );
        $this->remapearPublicaciones(
            ['Título Profesional' => 'Universitaria', 'CFT / Instituto Profesional' => 'Técnico profesional superior', 'Diplomado / Postítulo' => 'Diplomado'],
            array_flip(self::SITUACIONES),
        );

        CatalogosProfesionales::olvidar();
    }

    /**
     * Deja el catálogo administrable exactamente con estos términos y en este orden.
     *
     * @param  list<string>  $terminos
     */
    private function reescribirCatalogo(string $catalogo, array $terminos): void
    {
        DB::table('terminos_catalogo')->where('catalogo', $catalogo)->delete();

        $ahora = now();

        DB::table('terminos_catalogo')->insert(
            collect($terminos)->values()->map(fn (string $termino, int $orden): array => [
                'catalogo' => $catalogo,
                'valor' => $termino,
                'orden' => $orden,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->all()
        );
    }

    /**
     * `educaciones` es una columna json y hay que reescribirla entera por ficha; son
     * decenas de filas, no millones, así que se recorre en tandas y punto.
     *
     * @param  array<string, string>  $niveles
     * @param  array<string, string>  $situaciones
     */
    private function remapearFichas(array $niveles, array $situaciones): void
    {
        DB::table('postulantes')
            ->whereNotNull('educaciones')
            ->orderBy('id')
            ->chunk(200, function ($fichas) use ($niveles, $situaciones): void {
                foreach ($fichas as $ficha) {
                    $educaciones = json_decode((string) $ficha->educaciones, true);

                    if (! is_array($educaciones)) {
                        continue;
                    }

                    $migradas = array_map(function (mixed $educacion) use ($niveles, $situaciones): mixed {
                        if (! is_array($educacion)) {
                            return $educacion;
                        }

                        if (filled($educacion['nivel'] ?? null)) {
                            $educacion['nivel'] = $niveles[$educacion['nivel']] ?? $educacion['nivel'];
                        }

                        if (filled($educacion['situacion'] ?? null)) {
                            $educacion['situacion'] = $situaciones[$educacion['situacion']] ?? $educacion['situacion'];
                        }

                        return $educacion;
                    }, $educaciones);

                    if ($migradas !== $educaciones) {
                        DB::table('postulantes')
                            ->where('id', $ficha->id)
                            ->update(['educaciones' => json_encode($migradas, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
                    }
                }
            });
    }

    /**
     * @param  array<string, string>  $niveles
     * @param  array<string, string>  $situaciones
     */
    private function remapearPublicaciones(array $niveles, array $situaciones): void
    {
        foreach ($niveles as $viejo => $nuevo) {
            DB::table('publicaciones')->where('estudios_minimos', $viejo)->update(['estudios_minimos' => $nuevo]);
        }

        foreach ($situaciones as $viejo => $nuevo) {
            DB::table('publicaciones')->where('situacion_academica', $viejo)->update(['situacion_academica' => $nuevo]);
        }
    }
};
