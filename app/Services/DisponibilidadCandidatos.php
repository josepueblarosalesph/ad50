<?php

namespace App\Services;

use App\Models\Postulante;
use App\Support\CatalogosProfesionales;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Cuenta, para cada opción de un criterio, cuántas fichas quedarían si esa opción se
 * agregara a la búsqueda actual — es decir, un conteo **contextual**: ya descuenta los
 * demás criterios elegidos.
 *
 * Semántica de facetas (la misma de cualquier buscador con filtros): para el campo F, la
 * base son las fichas que cumplen todos los criterios EXCEPTO F. Se excluye F porque sus
 * valores se combinan con OR: si ya elegiste "Biobío", el conteo de "Valparaíso" debe
 * decir cuántas fichas sumarías al elegirlo, no cero. Como efecto, los números de un
 * campo no cambian al agregar más valores de ese mismo campo, y sí cambian al tocar
 * cualquier otro.
 *
 * Se calcula en UN solo recorrido para todos los campos: por ficha se evalúan los
 * criterios una vez y se mira cuáles falla. Si no falla ninguno entra en la base de
 * todos los campos; si falla exactamente uno, solo en la base de ese campo; si falla dos
 * o más, en ninguna. Eso evita recorrer las fichas una vez por campo.
 *
 * La evaluación reutiliza MatchingService, así que el número mostrado no puede divergir
 * de a quién entrega finalmente el motor de calce.
 */
class DisponibilidadCandidatos
{
    private const PREFIJO_CACHE = 'disponibilidad_candidatos:';

    private const CLAVE_VERSION = self::PREFIJO_CACHE.'version';

    /**
     * Las facetas se cachean por combinación de criterios, así que las claves no se
     * pueden enumerar para borrarlas: la invalidación bumpea una versión que forma parte
     * de la clave (ver olvidar()). El TTL acota la ventana si esa versión se pierde por
     * evicción, y cubre además las escrituras masivas por query builder, que no disparan
     * eventos de modelo (seeders, MatchingService).
     */
    private const TTL_SEGUNDOS = 600;

    /** @var array<string, array<string, array<string, int>>> */
    private array $memo = [];

    /** Campos que se muestran como combobox con conteo. */
    private const CAMPOS = [
        'cargo', 'carrera', 'industria', 'ciudad', 'habilidad', 'empresa', 'institucion',
        'situacion_laboral', 'genero', 'nivel_estudios', 'situacion_estudios', 'idioma', 'actividad_economica',
    ];

    public function __construct(private readonly MatchingService $matching) {}

    /**
     * Mapa opción => cuántas fichas quedarían al agregar esa opción al criterio $campo,
     * manteniendo el resto de $criterios. Las claves vienen normalizadas: usa clave().
     *
     * @param  array<string, mixed>  $criterios
     * @return array<string, int>
     */
    public function conteos(string $campo, array $criterios = []): array
    {
        if (! in_array($campo, self::CAMPOS, true)) {
            return [];
        }

        return $this->facetas($criterios)[$campo] ?? [];
    }

    /**
     * Conteos de todos los campos para una misma combinación de criterios.
     *
     * @param  array<string, mixed>  $criterios
     * @return array<string, array<string, int>>
     */
    public function facetas(array $criterios): array
    {
        $criterios = $this->soloCriteriosActivos($criterios);
        $huella = md5(json_encode($criterios, JSON_THROW_ON_ERROR));

        return $this->memo[$huella] ??= Cache::remember(
            self::PREFIJO_CACHE.'facetas:'.$this->version().':'.$huella,
            self::TTL_SEGUNDOS,
            fn (): array => $this->calcular($criterios),
        );
    }

    /**
     * Normaliza un valor para usarlo como clave de conteo. Replica la comparación del
     * motor de calce (case-insensitive y sin espacios al borde) para que una ficha
     * guardada como "biobío" cuente en la opción "Biobío".
     */
    public static function clave(string $valor): string
    {
        return Str::lower(trim($valor));
    }

    /**
     * Invalida los conteos cacheados. Se llama al crear, editar o borrar una ficha.
     */
    public static function olvidar(): void
    {
        Cache::increment(self::CLAVE_VERSION);
    }

    private function version(): int
    {
        return (int) Cache::get(self::CLAVE_VERSION, 0);
    }

    /**
     * Deja fuera los criterios vacíos para que combinaciones equivalentes compartan
     * entrada de caché, y ordena las claves para que el orden no genere huellas distintas.
     *
     * @param  array<string, mixed>  $criterios
     * @return array<string, mixed>
     */
    private function soloCriteriosActivos(array $criterios): array
    {
        $activos = array_filter(
            $criterios,
            fn (mixed $valor): bool => $valor !== null && $valor !== '' && $valor !== [] && $valor !== 0,
        );

        ksort($activos);

        return $activos;
    }

    /**
     * @param  array<string, mixed>  $criterios
     * @return array<string, array<string, int>>
     */
    private function calcular(array $criterios): array
    {
        /** @var array<string, array<string, int>> $conteos */
        $conteos = array_fill_keys(self::CAMPOS, []);

        Postulante::query()
            ->where('visible', true)
            ->chunkById(500, function ($fichas) use ($criterios, &$conteos): void {
                foreach ($fichas as $ficha) {
                    $incumplidos = array_keys(array_filter(
                        $this->matching->evaluar($ficha, $criterios),
                        fn (array $criterio): bool => ! $criterio['cumple'],
                    ));

                    // Falla más de un criterio: agregar una opción no la rescataría.
                    if (count($incumplidos) > 1) {
                        continue;
                    }

                    // Si falla exactamente uno, solo cuenta para la faceta de ese campo
                    // (es justo el criterio que la faceta deja fuera de su base).
                    $unicoIncumplido = $incumplidos[0] ?? null;

                    foreach (self::CAMPOS as $campo) {
                        if ($unicoIncumplido !== null && $unicoIncumplido !== $campo) {
                            continue;
                        }

                        foreach ($this->valoresDe($ficha, $campo) as $valor) {
                            $conteos[$campo][$valor] = ($conteos[$campo][$valor] ?? 0) + 1;
                        }
                    }
                }
            });

        return $conteos;
    }

    /**
     * Opciones que una ficha satisface para un campo, ya normalizadas y sin repetir.
     *
     * Los campos que el motor compara por substring (cargo, empresa, institución) se
     * cuentan por sus valores exactos: un criterio parcial como "Gerente" calzará con
     * más fichas de las que anuncia su opción.
     *
     * @return list<string>
     */
    private function valoresDe(Postulante $ficha, string $campo): array
    {
        $valores = match ($campo) {
            'cargo' => [
                ...$this->desdeJson($ficha->experiencias, ['cargo', 'cargo_otro', 'area']),
                $ficha->cargo_actual,
            ],
            'carrera' => [$ficha->carrera],
            'industria' => $ficha->industrias_interes ?? [],
            'ciudad' => $this->regiones($ficha),
            'habilidad' => $ficha->habilidades ?? [],
            'empresa' => [
                ...$this->desdeJson($ficha->experiencias, ['empresa', 'empresa_otro']),
                $ficha->empresa_actual,
            ],
            'institucion' => [
                ...$this->desdeJson($ficha->educaciones, ['institucion']),
                $ficha->universidad,
            ],
            'situacion_laboral' => [$ficha->situacion_laboral],
            'genero' => [$ficha->genero],
            'nivel_estudios' => $this->desdeJson($ficha->educaciones, ['nivel']),
            'situacion_estudios' => $this->desdeJson($ficha->educaciones, ['situacion']),
            'idioma' => collect($ficha->idiomas ?? [])
                ->map(fn (array $idioma): string => trim(($idioma['idioma'] ?? '').' · '.($idioma['nivel'] ?? '')))
                ->all(),
            'actividad_economica' => $this->desdeJson($ficha->experiencias, ['actividad_empresa']),
            default => [],
        };

        return collect($valores)
            ->filter(fn (mixed $valor): bool => is_string($valor) && filled(trim($valor)))
            ->map(fn (string $valor): string => self::clave($valor))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Regiones que la ficha satisface. "Nacional" en sus regiones de interés la hace
     * calzar con cualquier región chilena, tal como lo resuelve el motor de calce, así
     * que suma a todas las opciones salvo "Internacional".
     *
     * @return list<string>
     */
    private function regiones(Postulante $ficha): array
    {
        $interes = collect($ficha->regiones_interes ?? [])
            ->filter(fn (mixed $region): bool => is_string($region) && filled(trim($region)));

        $abiertoNacional = $interes->contains(fn (string $region): bool => self::clave($region) === 'nacional');

        if (! $abiertoNacional) {
            return $interes->values()->all();
        }

        return $interes
            ->concat(array_filter(
                CatalogosProfesionales::regionesInteres(),
                fn (string $region): bool => self::clave($region) !== 'internacional',
            ))
            ->values()
            ->all();
    }

    /**
     * Extrae propiedades de un array JSON de objetos (experiencias, educaciones, …).
     *
     * @param  list<string>  $propiedades
     * @return list<string>
     */
    private function desdeJson(mixed $coleccion, array $propiedades): array
    {
        return collect(is_array($coleccion) ? $coleccion : [])
            ->flatMap(fn (mixed $item): array => is_array($item)
                ? array_map(fn (string $propiedad): mixed => $item[$propiedad] ?? null, $propiedades)
                : [])
            ->filter(fn (mixed $valor): bool => is_string($valor) && filled(trim($valor)))
            ->values()
            ->all();
    }
}
