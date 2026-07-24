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
        'precio_uf' => 'decimal:2',
    ];

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
