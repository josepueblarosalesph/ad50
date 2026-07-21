<?php

namespace App\Models;

use App\Services\DisponibilidadCandidatos;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int|null $edad
 */
class Postulante extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'visible' => 'bool',
        'suscripcion_hasta' => 'date',
        'anio_nacimiento' => 'integer',
        'anios_experiencia' => 'integer',
        'expectativa_renta' => 'integer',
        'experiencia_inicio' => 'integer',
        'experiencia_fin' => 'integer',
        'regiones_interes' => 'array',
        'industrias_interes' => 'array',
        'habilidades' => 'array',
        'modalidad_trabajo' => 'array',
        'educaciones' => 'array',
        'idiomas' => 'array',
        'experiencias' => 'array',
        'onboarding_paso' => 'integer',
        'onboarding_completado' => 'boolean',
    ];

    /**
     * Cualquier cambio de ficha puede mover los conteos que anotan las opciones de los
     * combobox de búsqueda, así que se descartan de la caché.
     *
     * Ojo: las escrituras masivas por query builder (seeders, MatchingService) no
     * disparan eventos de modelo; para esas queda el TTL de DisponibilidadCandidatos.
     */
    protected static function booted(): void
    {
        static::saved(function (): void {
            DisponibilidadCandidatos::olvidar();
        });

        static::deleted(function (): void {
            DisponibilidadCandidatos::olvidar();
        });
    }

    /**
     * Solo guardamos el año de nacimiento, así que la edad es aproximada:
     * quien cumple años más adelante en el año figura con un año de más.
     *
     * @return Attribute<int|null, never>
     */
    protected function edad(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->anio_nacimiento === null
            ? null
            : now()->year - $this->anio_nacimiento);
    }

    /**
     * Última experiencia laboral (la más reciente) lista para mostrar en listados:
     * cargo, empresa y duración en el puesto. Null si no hay experiencias.
     *
     * @return array{cargo: string, empresa: string|null, duracion: string}|null
     */
    public function ultimaExperiencia(): ?array
    {
        $experiencias = collect($this->experiencias ?? [])
            ->filter(fn (mixed $e): bool => is_array($e) && filled($e['cargo'] ?? null));

        if ($experiencias->isEmpty()) {
            return null;
        }

        // La más reciente: primero la marcada como actual; luego por fecha de inicio desc.
        $e = $experiencias->sortByDesc(fn (array $exp): array => [
            ($exp['actualmente'] ?? false) ? 1 : 0,
            (int) ($exp['inicio_anio'] ?? $exp['inicio'] ?? 0),
            (int) ($exp['inicio_mes'] ?? 0),
        ])->first();

        $cargo = ($e['cargo'] ?? '') === 'Otros' ? ($e['cargo_otro'] ?? 'Otros') : ($e['cargo'] ?? '');
        $empresa = ($e['empresa'] ?? '') === 'Otros' ? ($e['empresa_otro'] ?? null) : ($e['empresa'] ?? null);

        return [
            'cargo' => $cargo,
            'empresa' => filled($empresa) ? $empresa : null,
            'duracion' => $this->duracionExperiencia($e),
        ];
    }

    /**
     * Duración en el puesto expresada en años (aprox.), a partir de las fechas de la
     * experiencia. Devuelve cadena vacía si no hay datos suficientes.
     *
     * @param  array<string, mixed>  $e
     */
    private function duracionExperiencia(array $e): string
    {
        $inicioAnio = (int) ($e['inicio_anio'] ?? $e['inicio'] ?? 0);

        if ($inicioAnio === 0) {
            return '';
        }

        $inicioMes = (int) ($e['inicio_mes'] ?? 1);

        if ($e['actualmente'] ?? false) {
            $finAnio = now()->year;
            $finMes = now()->month;
        } else {
            $finAnio = (int) ($e['fin_anio'] ?? $e['fin'] ?? 0);
            $finMes = (int) ($e['fin_mes'] ?? 12);
        }

        if ($finAnio === 0) {
            return '';
        }

        $meses = ($finAnio * 12 + $finMes) - ($inicioAnio * 12 + $inicioMes);
        $anios = intdiv(max($meses, 0), 12);

        if ($anios < 1) {
            return 'menos de 1 año';
        }

        return $anios.' '.($anios === 1 ? 'año' : 'años');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BusquedaCandidato::class);
    }
}
