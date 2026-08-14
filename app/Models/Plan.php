<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';

    protected $guarded = [];

    /** IVA vigente en Chile. Los precios se muestran en UF + IVA. */
    public const IVA = 0.19;

    protected $casts = [
        'features' => 'array',
        'destacado' => 'bool',
        'pago_unico' => 'bool',
        'max_contrataciones_anuales' => 'integer',
        'precio_uf' => 'decimal:2',
    ];

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
