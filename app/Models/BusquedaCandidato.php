<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusquedaCandidato extends Model
{
    protected $table = 'busqueda_candidato';

    protected $guarded = [];

    protected $casts = [
        'favorito' => 'boolean',
        'contactado_at' => 'datetime',
        'temporal' => 'boolean',
        'criterios_detalle' => 'array',
    ];

    /**
     * Solo coincidencias confirmadas del proceso (excluye las materializadas
     * temporalmente para previsualizar un borrador de filtros sin guardar).
     *
     * @param  Builder<BusquedaCandidato>  $query
     */
    public function scopeConfirmados(Builder $query): void
    {
        $query->where($query->qualifyColumn('temporal'), false);
    }

    /**
     * Solo coincidencias temporales de previsualización.
     *
     * @param  Builder<BusquedaCandidato>  $query
     */
    public function scopeTemporales(Builder $query): void
    {
        $query->where($query->qualifyColumn('temporal'), true);
    }

    public function busqueda(): BelongsTo
    {
        return $this->belongsTo(Busqueda::class);
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class);
    }
}
