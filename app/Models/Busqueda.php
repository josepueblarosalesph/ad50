<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Busqueda extends Model
{
    /**
     * Estados del proceso de selección (etapa del pipeline).
     *
     * @var array<string, string>
     */
    public const ESTADOS = [
        'long_list' => 'Long List',
        'short_list' => 'Short List',
        'entrevistas' => 'Entrevistas',
        'cancelado_cliente' => 'Cancelado por el Cliente',
        'cancelado' => 'Cancelado',
        'cerrado' => 'Cerrado',
        'pausado' => 'Pausado',
    ];

    /**
     * Estados en los que el proceso sigue vigente y participa del matching.
     *
     * @var list<string>
     */
    public const ESTADOS_ACTIVOS = ['long_list', 'short_list', 'entrevistas'];

    protected $guarded = [];

    protected $casts = ['criterios' => 'array'];

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function estaVigente(): bool
    {
        return in_array($this->estado, self::ESTADOS_ACTIVOS, true);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return HasMany<BusquedaCandidato, $this> */
    public function candidatos(): HasMany
    {
        return $this->hasMany(BusquedaCandidato::class);
    }
}
