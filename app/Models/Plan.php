<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';

    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'destacado' => 'bool',
        'precio_uf' => 'decimal:2',
    ];

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
