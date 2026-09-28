<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';

    protected $guarded = [];

    /** IVA vigente en Chile. Los precios se muestran en UF + IVA. */
    public const IVA = 0.19;

    /**
     * Plan que reciben todas las empresas al registrarse: gratis, sin cupos y sin
     * vencimiento. Es el único que la plataforma concede sola, y por eso su código está
     * fijo acá y no en el seeder: lo busca el registro de cada empresa nueva.
     */
    public const CODIGO_ILIMITADO = 'empresa_ilimitado';

    /**
     * Cupo de desbloqueos del plan ilimitado. Es un número y no NULL porque
     * `empresas.desbloqueos_cupo` no admite nulos, y ninguna empresa va a desbloquear un
     * millón de perfiles: a efectos prácticos es «sin tope», y así ni los cupos ni los
     * cobros necesitan un caso especial. Las publicaciones sí tienen NULL = ilimitadas.
     */
    public const CUPO_ILIMITADO = 1000000;

    /** Años de vigencia del plan ilimitado: no vence en ninguna vida útil del sistema. */
    public const ANIOS_ILIMITADO = 50;

    protected $casts = [
        'features' => 'json:unicode',
        'destacado' => 'bool',
        'pago_unico' => 'bool',
        'max_contrataciones_anuales' => 'integer',
        'precio_uf' => 'decimal:2',
    ];

    /**
     * Definición del plan ilimitado. Fuente única: la usan el seeder y ilimitado(), para
     * que un entorno donde nunca se corrió el seeder no quede sin él.
     *
     * @return array<string, mixed>
     */
    public static function definicionIlimitado(): array
    {
        return [
            'nombre' => 'Ilimitado',
            'audiencia' => 'empresa',
            'precio_clp' => 0,
            'precio_uf' => 0,
            'desbloqueos' => self::CUPO_ILIMITADO,
            'publicaciones' => null, // Ilimitadas.
            'periodo' => 'anual',
            'pago_unico' => false,
            'max_contrataciones_anuales' => null,
            'destacado' => false,
            'features' => ['Publicaciones ilimitadas', 'Match inteligente', 'Desbloqueos de perfiles ilimitados', 'Sin costo'],
            'recomendacion' => 'Incluido sin costo para todas las empresas.',
        ];
    }

    /**
     * El plan ilimitado, creándolo si falta.
     *
     * Se crea en vez de fallar porque de él depende que una empresa pueda registrarse: un
     * entorno sin seeder ni migración corrida dejaría el registro roto, y el plan no
     * tiene nada que configurar.
     */
    public static function ilimitado(): self
    {
        return self::query()->firstOrCreate(
            ['codigo' => self::CODIGO_ILIMITADO],
            self::definicionIlimitado(),
        );
    }

    /**
     * Planes que una empresa puede contratar. Deja fuera el ilimitado: no se vende, se
     * concede al registrarse, y ofrecerlo junto a los de pago sería regalar la plataforma
     * el día en que vuelva a cobrarse.
     *
     * @param  Builder<self>  $query
     */
    public function scopeContratables(Builder $query): void
    {
        $query->where('codigo', '!=', self::CODIGO_ILIMITADO);
    }

    /** Es el plan sin costo que reciben las empresas al registrarse. */
    public function esIlimitado(): bool
    {
        return $this->codigo === self::CODIGO_ILIMITADO;
    }

    /** Se cobra una sola vez y no se renueva solo; la vigencia la sigue dando `periodo`. */
    public function esPagoUnico(): bool
    {
        return (bool) $this->pago_unico;
    }

    /** Tiene tope de contrataciones por empresa en 12 meses. */
    public function tieneTopeAnual(): bool
    {
        return $this->max_contrataciones_anuales !== null;
    }

    public function periodoLabel(): string
    {
        if ($this->esPagoUnico()) {
            return 'pago único';
        }

        return $this->periodo === 'anual' ? 'al año' : 'al mes';
    }

    /**
     * Etiqueta de cobro bajo el precio en las tarjetas de planes.
     *
     * Vive en el modelo porque `periodo` por sí solo NO define cómo se cobra: un plan de
     * pago único conserva `periodo = 'anual'` (esa es su vigencia, ver vigenciaDesde()).
     * Decidirlo en la vista hacía que el plan Básico se anunciara como "plan anual".
     */
    public function cobroLabel(): string
    {
        if ($this->esPagoUnico()) {
            return 'pago único';
        }

        return $this->periodo === 'anual' ? 'plan anual' : 'plan mensual';
    }

    /** Precio del plan en CLP (UF × valor de la UF + IVA), redondeado a peso. */
    public function precioClp(float $valorUf): int
    {
        return (int) round((float) $this->precio_uf * $valorUf * (1 + self::IVA));
    }

    /**
     * Nueva fecha de vigencia al contratar este plan. Si ya hay una vigencia futura
     * (renovación), extiende desde ahí; si no, desde ahora. El período lo define el plan.
     */
    public function vigenciaDesde(?CarbonInterface $vigenciaActual = null): CarbonInterface
    {
        $base = $vigenciaActual !== null && $vigenciaActual->isFuture()
            ? $vigenciaActual
            : now();

        return match ($this->periodo) {
            'anual' => $base->addYear(),
            default => $base->addMonth(),
        };
    }
}
