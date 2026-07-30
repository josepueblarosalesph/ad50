<?php

namespace App\Support;

/**
 * Declara qué catálogos se administran desde el panel y, para cada uno, DÓNDE se usa
 * un término. Ese segundo dato es lo que permite responder "¿está en uso?" antes de
 * dejar editar o eliminar.
 *
 * Modos de uso:
 *  - `exacta`      la columna guarda el valor tal cual.
 *  - `lista_json`  la columna es una lista JSON de textos (`["Minería","Salud"]`).
 *  - `objetos_json` la columna es una lista JSON de objetos y el valor vive en una de
 *                  sus claves (`experiencias[].cargo`).
 *  - `criterio`    el valor quedó guardado como criterio de una búsqueda, dentro del
 *                  JSON `busquedas.criterios`.
 */
class CatalogosAdministrables
{
    /**
     * @return array<string, array{
     *     etiqueta: string,
     *     origen: string,
     *     usos: list<array{tabla: string, columna: string, modo: string, clave?: string, etiqueta: string}>
     * }>
     */
    public static function todos(): array
    {
        return [
            'industria' => [
                'etiqueta' => 'Industrias',
                'origen' => 'industrias',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'industrias_interes', 'modo' => 'lista_json', 'etiqueta' => 'industrias de interés de postulantes'],
                    ['tabla' => 'postulantes', 'columna' => 'experiencias', 'modo' => 'objetos_json', 'clave' => 'actividad_empresa', 'etiqueta' => 'experiencias de postulantes'],
                    ['tabla' => 'publicaciones', 'columna' => 'actividad_empresa', 'modo' => 'exacta', 'etiqueta' => 'publicaciones'],
                    ['tabla' => 'busquedas', 'columna' => 'industria', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                    ['tabla' => 'busquedas', 'columna' => 'actividad_economica', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'cargo' => [
                'etiqueta' => 'Cargos',
                'origen' => 'cargos',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'cargo_actual', 'modo' => 'exacta', 'etiqueta' => 'cargo actual de postulantes'],
                    ['tabla' => 'postulantes', 'columna' => 'experiencias', 'modo' => 'objetos_json', 'clave' => 'cargo', 'etiqueta' => 'experiencias de postulantes'],
                    ['tabla' => 'busquedas', 'columna' => 'cargo', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'cargo_area' => [
                'etiqueta' => 'Áreas de cargo',
                'origen' => 'cargosAreas',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'experiencia_area', 'modo' => 'exacta', 'etiqueta' => 'área de experiencia de postulantes'],
                    ['tabla' => 'postulantes', 'columna' => 'experiencias', 'modo' => 'objetos_json', 'clave' => 'area', 'etiqueta' => 'experiencias de postulantes'],
                ],
            ],
            'region' => [
                'etiqueta' => 'Regiones',
                'origen' => 'regionesInteres',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'regiones_interes', 'modo' => 'lista_json', 'etiqueta' => 'regiones de interés de postulantes'],
                    ['tabla' => 'postulantes', 'columna' => 'ciudad', 'modo' => 'exacta', 'etiqueta' => 'región de postulantes'],
                    ['tabla' => 'busquedas', 'columna' => 'ciudad', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'habilidad' => [
                'etiqueta' => 'Habilidades',
                'origen' => 'habilidades',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'habilidades', 'modo' => 'lista_json', 'etiqueta' => 'habilidades de postulantes'],
                    ['tabla' => 'busquedas', 'columna' => 'habilidad', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'carrera' => [
                'etiqueta' => 'Carreras',
                'origen' => 'carrerasEstudio',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'carrera', 'modo' => 'exacta', 'etiqueta' => 'carrera de postulantes'],
                    ['tabla' => 'postulantes', 'columna' => 'educaciones', 'modo' => 'objetos_json', 'clave' => 'carrera', 'etiqueta' => 'formación de postulantes'],
                    ['tabla' => 'busquedas', 'columna' => 'carrera', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'institucion' => [
                'etiqueta' => 'Instituciones de estudio',
                'origen' => 'instituciones',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'universidad', 'modo' => 'exacta', 'etiqueta' => 'institución de postulantes'],
                    ['tabla' => 'postulantes', 'columna' => 'educaciones', 'modo' => 'objetos_json', 'clave' => 'institucion', 'etiqueta' => 'formación de postulantes'],
                    ['tabla' => 'busquedas', 'columna' => 'institucion', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'empresa' => [
                'etiqueta' => 'Empresas',
                'origen' => 'empresas',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'empresa_actual', 'modo' => 'exacta', 'etiqueta' => 'empresa actual de postulantes'],
                    ['tabla' => 'postulantes', 'columna' => 'experiencias', 'modo' => 'objetos_json', 'clave' => 'empresa', 'etiqueta' => 'experiencias de postulantes'],
                    ['tabla' => 'busquedas', 'columna' => 'empresa', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'idioma' => [
                'etiqueta' => 'Idiomas',
                'origen' => 'idiomas',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'idiomas', 'modo' => 'objetos_json', 'clave' => 'idioma', 'etiqueta' => 'idiomas de postulantes'],
                    ['tabla' => 'publicaciones', 'columna' => 'idiomas', 'modo' => 'lista_json', 'etiqueta' => 'publicaciones'],
                ],
            ],
            'nivel_idioma' => [
                'etiqueta' => 'Niveles de idioma',
                'origen' => 'nivelesIdioma',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'idiomas', 'modo' => 'objetos_json', 'clave' => 'nivel', 'etiqueta' => 'idiomas de postulantes'],
                ],
            ],
            'jerarquia' => [
                'etiqueta' => 'Jerarquías',
                'origen' => 'jerarquias',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'experiencias', 'modo' => 'objetos_json', 'clave' => 'jerarquia', 'etiqueta' => 'experiencias de postulantes'],
                    ['tabla' => 'publicaciones', 'columna' => 'jerarquia', 'modo' => 'exacta', 'etiqueta' => 'publicaciones'],
                ],
            ],
            'tipo_trabajo' => [
                'etiqueta' => 'Tipos de jornada',
                'origen' => 'tiposTrabajo',
                'usos' => [
                    ['tabla' => 'publicaciones', 'columna' => 'tipo_cargo', 'modo' => 'exacta', 'etiqueta' => 'publicaciones'],
                ],
            ],
            'nivel_estudios' => [
                'etiqueta' => 'Niveles de estudio',
                'origen' => 'nivelesEstudio',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'educaciones', 'modo' => 'objetos_json', 'clave' => 'nivel', 'etiqueta' => 'formación de postulantes'],
                    ['tabla' => 'publicaciones', 'columna' => 'estudios_minimos', 'modo' => 'exacta', 'etiqueta' => 'publicaciones'],
                    ['tabla' => 'busquedas', 'columna' => 'nivel_estudios', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'situacion_estudios' => [
                'etiqueta' => 'Situaciones de estudio',
                'origen' => 'situacionesEstudio',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'educaciones', 'modo' => 'objetos_json', 'clave' => 'situacion', 'etiqueta' => 'formación de postulantes'],
                    ['tabla' => 'publicaciones', 'columna' => 'situacion_academica', 'modo' => 'exacta', 'etiqueta' => 'publicaciones'],
                    ['tabla' => 'busquedas', 'columna' => 'situacion_estudios', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'situacion_laboral' => [
                'etiqueta' => 'Situaciones laborales',
                'origen' => 'situacionesLaborales',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'situacion_laboral', 'modo' => 'exacta', 'etiqueta' => 'postulantes'],
                    ['tabla' => 'busquedas', 'columna' => 'situacion_laboral', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'genero' => [
                'etiqueta' => 'Géneros',
                'origen' => 'generos',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'genero', 'modo' => 'exacta', 'etiqueta' => 'postulantes'],
                    ['tabla' => 'busquedas', 'columna' => 'genero', 'modo' => 'criterio', 'etiqueta' => 'criterios de búsquedas'],
                ],
            ],
            'modalidad_trabajo' => [
                'etiqueta' => 'Modalidades de trabajo',
                'origen' => 'modalidadesTrabajoPreferidas',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'modalidad_trabajo', 'modo' => 'lista_json', 'etiqueta' => 'modalidad preferida de postulantes'],
                ],
            ],
            'nacionalidad' => [
                'etiqueta' => 'Nacionalidades',
                'origen' => 'nacionalidades',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'nacionalidad', 'modo' => 'exacta', 'etiqueta' => 'postulantes'],
                ],
            ],
            'pais' => [
                'etiqueta' => 'Países',
                'origen' => 'paises',
                'usos' => [
                    ['tabla' => 'postulantes', 'columna' => 'educaciones', 'modo' => 'objetos_json', 'clave' => 'pais', 'etiqueta' => 'formación de postulantes'],
                    ['tabla' => 'publicaciones', 'columna' => 'pais', 'modo' => 'exacta', 'etiqueta' => 'publicaciones'],
                ],
            ],
        ];
    }

    /** @return list<string> */
    public static function claves(): array
    {
        return array_keys(self::todos());
    }

    public static function existe(string $catalogo): bool
    {
        return array_key_exists($catalogo, self::todos());
    }

    /** @return array{etiqueta: string, origen: string, usos: list<array<string, string>>} */
    public static function definicion(string $catalogo): array
    {
        return self::todos()[$catalogo] ?? throw new \InvalidArgumentException("Catálogo desconocido: {$catalogo}");
    }
}
