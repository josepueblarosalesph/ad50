<?php

namespace App\Services;

use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Postulante;
use Illuminate\Support\Str;

class MatchingService
{
    /**
     * Recalcula las coincidencias de un proceso contra todos los postulantes visibles.
     *
     * Evalúa en memoria y escribe en lote (un upsert + un delete) para no hacer una
     * consulta por postulante: con volúmenes grandes esto es órdenes de magnitud más rápido.
     */
    public function sincronizar(Busqueda $busqueda): void
    {
        $criterios = $busqueda->criterios ?? [];
        $ahora = now();
        $filas = [];
        $cumpleIds = [];

        Postulante::query()
            ->where('visible', true)
            ->chunkById(500, function ($postulantes) use ($busqueda, $criterios, $ahora, &$filas, &$cumpleIds): void {
                foreach ($postulantes as $postulante) {
                    $detalle = $this->evaluar($postulante, $criterios);

                    if (collect($detalle)->contains(fn (array $criterio): bool => ! $criterio['cumple'])) {
                        continue;
                    }

                    $total = count($detalle);
                    $cumpleIds[] = $postulante->id;
                    $filas[] = [
                        'busqueda_id' => $busqueda->id,
                        'postulante_id' => $postulante->id,
                        'match_score' => 100,
                        'criterios_cumplidos' => $total,
                        'criterios_totales' => $total,
                        'criterios_detalle' => json_encode(array_values($detalle), JSON_THROW_ON_ERROR),
                        'estado_match' => 'cumple',
                        'temporal' => false,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ];
                }
            });

        // Upsert de las coincidencias por tandas (preserva favorito y contactado_at existentes).
        // `temporal => false` confirma cualquier fila que existiera como previsualización.
        foreach (array_chunk($filas, 500) as $tanda) {
            BusquedaCandidato::query()->upsert(
                $tanda,
                ['busqueda_id', 'postulante_id'],
                ['match_score', 'criterios_cumplidos', 'criterios_totales', 'criterios_detalle', 'estado_match', 'temporal', 'updated_at'],
            );
        }

        // Quitar de este proceso a los postulantes que dejaron de cumplir (o ya no son visibles).
        $busqueda->candidatos()
            ->when($cumpleIds !== [], fn ($query) => $query->whereNotIn('postulante_id', $cumpleIds))
            ->delete();
    }

    public function sincronizarPostulante(Postulante $postulante): void
    {
        Busqueda::query()
            ->whereIn('estado', Busqueda::ESTADOS_ACTIVOS)
            ->each(function (Busqueda $busqueda) use ($postulante): void {
                if (! $postulante->visible) {
                    $busqueda->candidatos()->whereBelongsTo($postulante)->delete();

                    return;
                }

                $this->guardarCoincidencia($busqueda, $postulante);
            });
    }

    /**
     * @param  array<string, mixed>  $criterios
     * @return array<string, array{criterio: string, valor: string, cumple: bool}>
     */
    public function evaluar(Postulante $postulante, array $criterios): array
    {
        $experiencias = $postulante->experiencias ?? [];
        $cargos = collect($experiencias)
            ->flatMap(fn (array $experiencia): array => [$experiencia['cargo'] ?? '', $experiencia['cargo_otro'] ?? '', $experiencia['area'] ?? ''])
            ->push($postulante->cargo_actual)
            ->filter();

        $evaluadores = [
            'cargo' => ['Cargo', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => $cargos->contains(fn (?string $cargo): bool => $this->coincideCargo($cargo, $valor)))],
            'carrera' => ['Carrera o título', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => $this->iguales($postulante->carrera, $valor))],
            'especialidad' => ['Especialidad / área', fn (string $valor): bool => Str::contains(Str::lower(trim((string) $postulante->especialidad)), Str::lower(trim($valor)))],
            'industria' => ['Industria', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => collect($postulante->industrias_interes ?? [])->contains(fn (?string $industria): bool => $this->iguales($industria, $valor)))],
            'ciudad' => ['Región', function (array $valores) use ($postulante): bool {
                $interes = collect($postulante->regiones_interes ?? []);
                $abiertoNacional = $interes->contains(fn (?string $region): bool => $this->iguales($region, 'Nacional'));

                return collect($valores)->contains(function (string $valor) use ($interes, $abiertoNacional): bool {
                    // "Nacional" en las regiones de interés cubre cualquier región chilena, pero no "Internacional".
                    if ($abiertoNacional && ! $this->iguales($valor, 'Internacional')) {
                        return true;
                    }

                    return $interes->contains(fn (?string $region): bool => $this->iguales($region, $valor));
                });
            }],
            'habilidad' => ['Habilidades', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => collect($postulante->habilidades ?? [])->contains(fn (?string $habilidad): bool => $this->iguales($habilidad, $valor)))],
            'institucion' => ['Institución de estudio', function (string $valor) use ($postulante): bool {
                $instituciones = collect($postulante->educaciones ?? [])
                    ->pluck('institucion')
                    ->push($postulante->universidad)
                    ->filter();

                return $instituciones->contains(fn (string $institucion): bool => Str::contains(Str::lower($institucion), Str::lower($valor)));
            }],
            'empresa' => ['Empresa', function (string $valor) use ($postulante): bool {
                $empresas = collect($postulante->experiencias ?? [])
                    ->flatMap(fn (array $experiencia): array => [$experiencia['empresa'] ?? '', $experiencia['empresa_otro'] ?? ''])
                    ->push($postulante->empresa_actual)
                    ->filter();

                return $empresas->contains(fn (string $empresa): bool => Str::contains(Str::lower($empresa), Str::lower($valor)));
            }],
            'situacion_laboral' => ['Situación laboral', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => $this->iguales($postulante->situacion_laboral, $valor))],
            'genero' => ['Género', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => $this->iguales($postulante->genero, $valor))],
            'nivel_estudios' => ['Nivel de estudios', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => collect($postulante->educaciones ?? [])->contains(fn (array $educacion): bool => $this->iguales($educacion['nivel'] ?? null, $valor)))],
            'situacion_estudios' => ['Situación de estudios', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => collect($postulante->educaciones ?? [])->contains(fn (array $educacion): bool => $this->iguales($educacion['situacion'] ?? null, $valor)))],
            'idioma' => ['Idioma', function (array $valores) use ($postulante): bool {
                $combos = collect($postulante->idiomas ?? [])
                    ->map(fn (array $idioma): string => trim(($idioma['idioma'] ?? '').' · '.($idioma['nivel'] ?? '')));

                return collect($valores)->contains(fn (string $valor): bool => $combos->contains(fn (string $combo): bool => $this->iguales($combo, $valor)));
            }],
            'actividad_economica' => ['Actividad económica', fn (array $valores): bool => collect($valores)->contains(fn (string $valor): bool => collect($postulante->experiencias ?? [])->contains(fn (array $experiencia): bool => $this->iguales($experiencia['actividad_empresa'] ?? null, $valor)))],
            'renta_max' => ['Expectativa de renta', fn (string $valor): bool => $postulante->expectativa_renta !== null && $postulante->expectativa_renta <= (int) $valor],
            'min_anios' => ['Experiencia mínima', fn (string $valor): bool => $postulante->anios_experiencia >= (int) $valor],
            'experiencia' => ['Años de experiencia', fn (array $valor): bool => $postulante->anios_experiencia >= (int) $valor['min']
                && ($valor['max'] === null || $postulante->anios_experiencia <= (int) $valor['max'])],
            'edad' => ['Edad', function (array $valor) use ($postulante): bool {
                if ($postulante->edad === null) {
                    return false;
                }

                return $postulante->edad >= (int) $valor['min']
                    && ($valor['max'] === null || $postulante->edad <= (int) $valor['max']);
            }],
            'palabra_clave' => ['Palabra clave', function (array $valores) use ($postulante, $cargos): bool {
                $responsabilidades = collect($postulante->experiencias ?? [])->pluck('responsabilidades');
                $texto = $cargos
                    ->concat($responsabilidades)
                    ->push($postulante->resumen_profesional)
                    ->filter()
                    ->implode(' ');

                return collect($valores)->contains(fn (string $valor): bool => Str::contains(Str::lower($texto), Str::lower($valor)));
            }],
        ];

        $detalle = [];

        foreach ($evaluadores as $clave => [$etiqueta, $evaluar]) {
            $valor = $criterios[$clave] ?? null;

            if ($valor === null || $valor === '' || $valor === [] || (in_array($clave, ['min_anios', 'renta_max'], true) && (int) $valor === 0)) {
                continue;
            }

            if (in_array($clave, ['edad', 'experiencia'], true)) {
                $detalle[$clave] = [
                    'criterio' => $etiqueta,
                    'valor' => $this->rangoEdadLegible($valor),
                    'cumple' => $evaluar($valor),
                ];

                continue;
            }

            $esSeleccionMultiple = in_array($clave, ['cargo', 'carrera', 'industria', 'ciudad', 'habilidad', 'situacion_laboral', 'genero', 'nivel_estudios', 'situacion_estudios', 'idioma', 'actividad_economica', 'palabra_clave'], true);
            // Los criterios de valor único que quedaron guardados como array (dato legacy) toman su primer valor.
            $valorUnico = is_array($valor) ? (string) (collect($valor)->first() ?? '') : (string) $valor;
            $valorEvaluado = $esSeleccionMultiple ? array_values(array_filter((array) $valor, filled(...))) : $valorUnico;
            $valorMostrado = $esSeleccionMultiple ? implode(', ', $valorEvaluado) : $valorUnico;

            if ($valorEvaluado === []) {
                continue;
            }

            $detalle[$clave] = [
                'criterio' => $etiqueta,
                'valor' => match ($clave) {
                    'min_anios' => $valor.' años',
                    'renta_max' => 'hasta $'.number_format((int) $valor, 0, ',', '.'),
                    default => $valorMostrado,
                },
                'cumple' => $evaluar($valorEvaluado),
            ];
        }

        return $detalle;
    }

    /** @param  array{min: int, max: int|null}  $rango */
    private function rangoEdadLegible(array $rango): string
    {
        return $rango['max'] === null
            ? $rango['min'].' años o más'
            : $rango['min'].' a '.$rango['max'].' años';
    }

    private function iguales(?string $actual, string $esperado): bool
    {
        return Str::lower(trim((string) $actual)) === Str::lower(trim($esperado));
    }

    private function coincideCargo(?string $actual, string $esperado): bool
    {
        $cargo = Str::lower(trim((string) $actual));
        $criterio = Str::lower(trim($esperado));

        return $cargo === $criterio || Str::contains($cargo, $criterio);
    }

    private function guardarCoincidencia(Busqueda $busqueda, Postulante $postulante): void
    {
        $detalle = $this->evaluar($postulante, $busqueda->criterios ?? []);
        $incumpleCriterio = collect($detalle)->contains(fn (array $criterio): bool => ! $criterio['cumple']);

        if ($incumpleCriterio) {
            $busqueda->candidatos()->whereBelongsTo($postulante)->delete();

            return;
        }

        $cumplidos = collect($detalle)->where('cumple', true)->count();
        $total = count($detalle);

        $busqueda->candidatos()->updateOrCreate(
            ['postulante_id' => $postulante->id],
            [
                'match_score' => $total === 0 ? 100 : (int) round(($cumplidos / $total) * 100),
                'criterios_cumplidos' => $cumplidos,
                'criterios_totales' => $total,
                'criterios_detalle' => array_values($detalle),
                'estado_match' => $cumplidos === $total ? 'cumple' : 'parcial',
                'temporal' => false,
            ],
        );
    }

    /**
     * Materializa como filas TEMPORALES las coincidencias de un borrador de filtros sin
     * guardar, para que la previsualización cuente y permita abrir todos los perfiles.
     * No toca las filas confirmadas (conserva favoritos ni altera su detalle guardado):
     * solo crea filas nuevas para los perfiles que aún no están en el proceso y descarta
     * las temporales que dejaron de cumplir el borrador.
     *
     * @param  array<string, mixed>  $criterios
     */
    public function previsualizar(Busqueda $busqueda, array $criterios): void
    {
        $ahora = now();
        $cumpleIds = [];
        $detallePorId = [];

        Postulante::query()
            ->where('visible', true)
            ->chunkById(500, function ($postulantes) use ($criterios, &$cumpleIds, &$detallePorId): void {
                foreach ($postulantes as $postulante) {
                    $detalle = $this->evaluar($postulante, $criterios);

                    if (collect($detalle)->contains(fn (array $criterio): bool => ! $criterio['cumple'])) {
                        continue;
                    }

                    $cumpleIds[] = $postulante->id;
                    $detallePorId[$postulante->id] = array_values($detalle);
                }
            });

        // Perfiles del borrador que aún no tienen fila en el proceso (ni confirmada ni temporal).
        $existentes = $busqueda->candidatos()->pluck('postulante_id')->all();
        $nuevos = array_values(array_diff($cumpleIds, $existentes));

        $filas = [];
        foreach ($nuevos as $postulanteId) {
            $total = count($detallePorId[$postulanteId]);
            $filas[] = [
                'busqueda_id' => $busqueda->id,
                'postulante_id' => $postulanteId,
                'match_score' => 100,
                'criterios_cumplidos' => $total,
                'criterios_totales' => $total,
                'criterios_detalle' => json_encode($detallePorId[$postulanteId], JSON_THROW_ON_ERROR),
                'estado_match' => 'cumple',
                'temporal' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        foreach (array_chunk($filas, 500) as $tanda) {
            BusquedaCandidato::query()->insert($tanda);
        }

        // Descarta las filas temporales que ya no cumplen el borrador (de una edición previa).
        $busqueda->candidatos()
            ->temporales()
            ->when($cumpleIds !== [], fn ($query) => $query->whereNotIn('postulante_id', $cumpleIds))
            ->delete();
    }

    /** Elimina todas las coincidencias temporales de previsualización de una búsqueda. */
    public function limpiarTemporales(Busqueda $busqueda): void
    {
        $busqueda->candidatos()->temporales()->delete();
    }
}
