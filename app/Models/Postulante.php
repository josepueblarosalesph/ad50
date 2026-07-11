<?php

namespace App\Models;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BusquedaCandidato::class);
    }
}
