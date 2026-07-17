<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Cuenta cuántos postulantes visibles hay disponibles por cada valor de un criterio.
 *
 * Se usa para anotar las opciones de los combobox de búsqueda con "N candidatos".
 * Cada campo se resuelve con UNA sola agregación SQL (sobre columnas o arrays JSON
 * casteados a jsonb), y el mapa resultante se memoiza por instancia para no repetir
 * la consulta al listar varias opciones en un mismo render.
 *
 * Los conteos son indicativos: para campos que en el matching usan coincidencia por
 * substring (cargo, empresa, institución) se cuentan las coincidencias exactas del
 * catálogo, que cubren la gran mayoría de los casos.
 */
class DisponibilidadCandidatos
{
    /** @var array<string, array<string, int>> */
    private array $memo = [];

    /** Campos soportados y cómo se cuentan. */
    private const CAMPOS = [
        'cargo', 'carrera', 'industria', 'ciudad', 'habilidad', 'empresa', 'institucion',
        'situacion_laboral', 'genero', 'nivel_estudios', 'situacion_estudios', 'idioma', 'actividad_economica',
    ];

    /**
     * Mapa valor => cantidad de postulantes visibles disponibles para ese valor.
     *
     * @return array<string, int>
     */
    public function conteos(string $campo): array
    {
        if (! in_array($campo, self::CAMPOS, true)) {
            return [];
        }

        return $this->memo[$campo] ??= $this->calcular($campo);
    }

    /** @return array<string, int> */
    private function calcular(string $campo): array
    {
        $filas = match ($campo) {
            'carrera' => $this->porColumna('carrera'),
            'industria' => $this->porArrayJson('industrias_interes'),
            'ciudad' => $this->porArrayJson('regiones_interes'),
            'habilidad' => $this->porArrayJson('habilidades'),
            'cargo' => $this->porObjetosJson('experiencias', 'cargo', 'cargo_actual'),
            'empresa' => $this->porObjetosJson('experiencias', 'empresa', 'empresa_actual'),
            'institucion' => $this->porObjetosJson('educaciones', 'institucion', 'universidad'),
            'situacion_laboral' => $this->porColumna('situacion_laboral'),
            'genero' => $this->porColumna('genero'),
            'nivel_estudios' => $this->porPropiedadJson('educaciones', 'nivel'),
            'situacion_estudios' => $this->porPropiedadJson('educaciones', 'situacion'),
            'idioma' => $this->porParPropiedadesJson('idiomas', 'idioma', 'nivel', ' · '),
            'actividad_economica' => $this->porPropiedadJson('experiencias', 'actividad_empresa'),
            default => [],
        };

        $mapa = [];

        foreach ($filas as $fila) {
            $mapa[(string) $fila->valor] = (int) $fila->total;
        }

        return $mapa;
    }

    /**
     * Conteo por una columna de texto simple.
     *
     * @return list<object>
     */
    private function porColumna(string $columna): array
    {
        return DB::table('postulantes')
            ->selectRaw("{$columna} AS valor, COUNT(*) AS total")
            ->where('visible', true)
            ->whereNotNull($columna)
            ->where($columna, '<>', '')
            ->groupBy($columna)
            ->get()
            ->all();
    }

    /**
     * Conteo por un array JSON de strings (regiones_interes, industrias_interes, habilidades).
     *
     * @return list<object>
     */
    private function porArrayJson(string $columna): array
    {
        return DB::table('postulantes')
            ->crossJoin(DB::raw("jsonb_array_elements_text({$columna}::jsonb) AS elem"))
            ->selectRaw('elem AS valor, COUNT(*) AS total')
            ->where('visible', true)
            ->whereNotNull($columna)
            ->groupBy('elem')
            ->get()
            ->all();
    }

    /**
     * Conteo por una propiedad dentro de un array JSON de objetos (sin columna extra).
     * No double-cuenta un postulante que repite el mismo valor.
     *
     * @return list<object>
     */
    private function porPropiedadJson(string $columnaJson, string $propiedad): array
    {
        $desdeJson = DB::table('postulantes')
            ->crossJoin(DB::raw("jsonb_array_elements({$columnaJson}::jsonb) AS obj"))
            ->selectRaw("id, (obj->>'{$propiedad}') AS valor")
            ->where('visible', true)
            ->whereNotNull($columnaJson);

        return DB::query()
            ->fromSub($desdeJson, 't')
            ->selectRaw('valor, COUNT(DISTINCT id) AS total')
            ->whereNotNull('valor')
            ->where('valor', '<>', '')
            ->groupBy('valor')
            ->get()
            ->all();
    }

    /**
     * Conteo por la concatenación de dos propiedades dentro de un array JSON de objetos.
     * Ej.: "Inglés · Avanzado" a partir de idioma y nivel.
     *
     * @return list<object>
     */
    private function porParPropiedadesJson(string $columnaJson, string $prop1, string $prop2, string $separador): array
    {
        $desdeJson = DB::table('postulantes')
            ->crossJoin(DB::raw("jsonb_array_elements({$columnaJson}::jsonb) AS obj"))
            ->selectRaw("id, ((obj->>'{$prop1}') || ? || (obj->>'{$prop2}')) AS valor", [$separador])
            ->where('visible', true)
            ->whereNotNull($columnaJson);

        return DB::query()
            ->fromSub($desdeJson, 't')
            ->selectRaw('valor, COUNT(DISTINCT id) AS total')
            ->whereNotNull('valor')
            ->where('valor', '<>', '')
            ->groupBy('valor')
            ->get()
            ->all();
    }

    /**
     * Conteo por una propiedad dentro de un array JSON de objetos, más una columna suelta.
     * Ej.: cargos de cada experiencia + cargo_actual. No double-cuenta un postulante.
     *
     * @return list<object>
     */
    private function porObjetosJson(string $columnaJson, string $propiedad, string $columnaExtra): array
    {
        $desdeJson = DB::table('postulantes')
            ->crossJoin(DB::raw("jsonb_array_elements({$columnaJson}::jsonb) AS obj"))
            ->selectRaw("id, (obj->>'{$propiedad}') AS valor")
            ->where('visible', true)
            ->whereNotNull($columnaJson);

        $desdeColumna = DB::table('postulantes')
            ->selectRaw("id, {$columnaExtra} AS valor")
            ->where('visible', true)
            ->whereNotNull($columnaExtra);

        return DB::query()
            ->fromSub($desdeJson->unionAll($desdeColumna), 't')
            ->selectRaw('valor, COUNT(DISTINCT id) AS total')
            ->whereNotNull('valor')
            ->where('valor', '<>', '')
            ->groupBy('valor')
            ->get()
            ->all();
    }
}
