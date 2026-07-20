<?php

namespace Database\Seeders;

use App\Models\Busqueda;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Services\MatchingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Procesos de demo con la forma actual de criterios (la que arma el panel de filtros:
 * cargo, carrera, industria, región, habilidad, situación laboral, género, nivel y
 * situación de estudios, idioma, actividad económica, renta, institución, empresa,
 * rangos de experiencia y edad, palabras clave).
 *
 * Cada proceso se construye **a partir de un postulante visible real** de la base: los
 * criterios se derivan de su propia ficha, de modo que ese postulante calza por
 * construcción y el proceso nunca queda vacío. Las recetas solo eligen qué criterios
 * derivar, así el mismo seeder produce procesos compatibles con cualquier set de
 * postulantes (los de demo o los de PostulantesPruebaSeeder).
 *
 * ⚠️ Es destructivo por diseño: borra **todas** las búsquedas existentes (y con ellas,
 * por FK en cascada, sus filas de busqueda_candidato) antes de crear las nuevas.
 */
class BusquedaSeeder extends Seeder
{
    public function run(MatchingService $matching): void
    {
        $postulantes = Postulante::query()->where('visible', true)->orderBy('id')->get();

        if ($postulantes->isEmpty()) {
            $this->avisar('BusquedaSeeder: no hay postulantes visibles, no se crean procesos.');

            return;
        }

        $empresas = $this->empresasActivas();

        if ($empresas->isEmpty()) {
            $this->avisar('BusquedaSeeder: no hay empresas activas, ejecuta antes EmpresaSeeder.');

            return;
        }

        // Fuera los procesos antiguos (criterios en el formato viejo) y sus candidatos.
        Busqueda::query()->delete();

        $recetas = $this->recetas();

        foreach ($recetas as $indice => $receta) {
            $ancla = $this->ancla($postulantes, $indice, count($recetas));
            $criterios = $this->criteriosDesde($ancla, $receta['campos']);

            $busqueda = Busqueda::query()->create([
                'empresa_id' => $empresas->values()->get($indice % $empresas->count())->id,
                'titulo' => $this->titulo($receta['titulo'], $ancla),
                'rubro_oculto' => $criterios['industria'][0] ?? null,
                'criterios' => $criterios,
                'estado' => $receta['estado'],
            ]);

            $matching->sincronizar($busqueda);
        }

        $this->avisar('BusquedaSeeder: '.count($recetas).' procesos creados con criterios derivados de fichas reales.');
    }

    /**
     * Mensaje por consola. Igual que el propio Seeder de Laravel, el comando solo existe
     * cuando se corre vía `db:seed`, no al instanciar el seeder a mano.
     */
    private function avisar(string $mensaje): void
    {
        $this->command?->info($mensaje);
    }

    /**
     * Recetas de procesos. `campos` decide qué criterios se derivan del postulante ancla:
     * pocas claves ⇒ proceso amplio con muchos calces; muchas ⇒ proceso muy acotado.
     *
     * @return list<array{titulo: string, estado: string, campos: list<string>}>
     */
    private function recetas(): array
    {
        return [
            ['titulo' => 'Búsqueda amplia de talento senior', 'estado' => 'long_list', 'campos' => ['experiencia']],
            ['titulo' => 'Perfil por industria y región', 'estado' => 'long_list', 'campos' => ['industria', 'ciudad']],
            ['titulo' => 'Liderazgo por cargo', 'estado' => 'long_list', 'campos' => ['cargo', 'experiencia']],
            ['titulo' => 'Formación universitaria titulada', 'estado' => 'long_list', 'campos' => ['nivel_estudios', 'situacion_estudios', 'ciudad']],
            ['titulo' => 'Bilingüe para rol regional', 'estado' => 'short_list', 'campos' => ['idioma', 'industria', 'experiencia']],
            ['titulo' => 'Especialista por habilidades', 'estado' => 'long_list', 'campos' => ['habilidad', 'experiencia']],
            ['titulo' => 'Disponibilidad y expectativa de renta', 'estado' => 'long_list', 'campos' => ['situacion_laboral', 'renta_max']],
            ['titulo' => 'Trayectoria por actividad económica', 'estado' => 'short_list', 'campos' => ['actividad_economica', 'experiencia', 'edad']],
            ['titulo' => 'Carrera específica con experiencia acotada', 'estado' => 'long_list', 'campos' => ['carrera', 'experiencia']],
            ['titulo' => 'Egresados de una casa de estudios', 'estado' => 'long_list', 'campos' => ['institucion', 'nivel_estudios']],
            ['titulo' => 'Búsqueda por palabra clave', 'estado' => 'entrevistas', 'campos' => ['palabra_clave', 'ciudad']],
            ['titulo' => 'Proceso ejecutivo muy acotado', 'estado' => 'entrevistas', 'campos' => ['cargo', 'industria', 'ciudad', 'experiencia', 'edad', 'nivel_estudios']],
        ];
    }

    /**
     * Reparte los postulantes ancla a lo largo de la colección para que los procesos
     * no queden todos apuntando al mismo perfil.
     *
     * @param  Collection<int, Postulante>  $postulantes
     */
    private function ancla(Collection $postulantes, int $indice, int $total): Postulante
    {
        return $postulantes->values()->get(intdiv($indice * $postulantes->count(), $total));
    }

    /**
     * Deriva los criterios pedidos desde la ficha del ancla. Un campo sin dato en la
     * ficha simplemente se omite: mejor un proceso con un criterio menos que uno
     * imposible de cumplir.
     *
     * @param  list<string>  $campos
     * @return array<string, mixed>
     */
    private function criteriosDesde(Postulante $ancla, array $campos): array
    {
        $criterios = [];

        foreach ($campos as $campo) {
            $valor = match ($campo) {
                'cargo' => $this->lista([$ancla->cargo_actual]),
                'carrera' => $this->lista([$ancla->carrera]),
                'industria' => array_slice($this->lista($ancla->industrias_interes ?? []), 0, 2),
                'ciudad' => $this->lista([$this->region($ancla)]),
                'habilidad' => array_slice($this->lista($ancla->habilidades ?? []), 0, 2),
                'situacion_laboral' => $this->lista([$ancla->situacion_laboral]),
                'genero' => $this->lista([$ancla->genero]),
                'nivel_estudios' => $this->lista(collect($ancla->educaciones ?? [])->pluck('nivel')->all()),
                'situacion_estudios' => $this->lista(collect($ancla->educaciones ?? [])->pluck('situacion')->all()),
                'idioma' => $this->lista(collect($ancla->idiomas ?? [])
                    ->map(fn (array $idioma): string => trim(($idioma['idioma'] ?? '').' · '.($idioma['nivel'] ?? '')))
                    ->all()),
                'actividad_economica' => $this->lista(collect($ancla->experiencias ?? [])->pluck('actividad_empresa')->all()),
                'institucion' => $ancla->universidad ?: (collect($ancla->educaciones ?? [])->pluck('institucion')->filter()->first() ?? ''),
                'empresa' => (string) ($ancla->empresa_actual ?? ''),
                'especialidad' => (string) ($ancla->especialidad ?? ''),
                'renta_max' => (int) ($ancla->expectativa_renta ?? 0),
                'experiencia' => $this->rangoExperiencia($ancla),
                'edad' => $this->rangoEdad($ancla),
                'palabra_clave' => $this->lista([$this->palabraClave($ancla)]),
                default => null,
            };

            if ($valor === null || $valor === '' || $valor === [] || $valor === 0) {
                continue;
            }

            $criterios[$campo] = $valor;
        }

        return $criterios;
    }

    /**
     * Región de interés concreta del ancla. "Nacional" cubre cualquier región, así que
     * como criterio se prefiere una región real (la del ancla) para que el proceso
     * discrimine de verdad.
     */
    private function region(Postulante $ancla): string
    {
        $concreta = collect($ancla->regiones_interes ?? [])
            ->filter(fn (?string $region): bool => filled($region) && ! in_array($region, ['Nacional', 'Internacional'], true))
            ->first();

        return (string) ($concreta ?? $ancla->ciudad ?? '');
    }

    /** @return array{min: int, max: int|null} */
    private function rangoExperiencia(Postulante $ancla): array
    {
        $anios = (int) $ancla->anios_experiencia;

        return ['min' => max(0, $anios - 5), 'max' => $anios + 5];
    }

    /** @return array{min: int, max: int|null}|null */
    private function rangoEdad(Postulante $ancla): ?array
    {
        if ($ancla->edad === null) {
            return null;
        }

        return ['min' => max(50, $ancla->edad - 5), 'max' => $ancla->edad + 5];
    }

    /**
     * Primera palabra significativa del cargo del ancla: aparece en sus cargos y
     * responsabilidades, que es donde busca el criterio de palabra clave.
     */
    private function palabraClave(Postulante $ancla): string
    {
        return collect(explode(' ', (string) $ancla->cargo_actual))
            ->first(fn (string $palabra): bool => mb_strlen($palabra) >= 5) ?? '';
    }

    /**
     * @param  array<int, mixed>  $valores
     * @return list<string>
     */
    private function lista(array $valores): array
    {
        return array_values(collect($valores)
            ->filter(fn (mixed $valor): bool => is_string($valor) && filled(trim($valor)))
            ->map(fn (string $valor): string => trim($valor))
            ->unique()
            ->all());
    }

    private function titulo(string $base, Postulante $ancla): string
    {
        $detalle = $ancla->cargo_actual ?: $ancla->carrera;

        return Str::limit($detalle ? $base.' · '.$detalle : $base, 120, '');
    }

    /** @return Collection<int, Empresa> */
    private function empresasActivas(): Collection
    {
        return Empresa::query()
            ->where('estado_activacion', 'activa')
            ->orderBy('id')
            ->get();
    }
}
