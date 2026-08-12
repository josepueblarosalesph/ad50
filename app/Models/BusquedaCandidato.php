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
        'contactado_at' => 'datetime',
        'temporal' => 'boolean',
        'criterios_detalle' => 'array',
    ];

    /**
     * Solo coincidencias confirmadas de la búsqueda (excluye las materializadas
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

    /** @return BelongsTo<Busqueda, $this> */
    /**
     * Búsqueda a la que pertenece la coincidencia, incluidas las que están en la
     * papelera: un favorito sobrevive a que su búsqueda se elimine, y al abrir su perfil
     * hay que poder llegar igual a la coincidencia de origen. Sin `withTrashed()` la
     * relación devolvería null y la ficha reventaría al comprobar de quién es.
     *
     * @return BelongsTo<Busqueda, $this>
     */
    public function busqueda(): BelongsTo
    {
        return $this->belongsTo(Busqueda::class)->withTrashed();
    }

    /** @return BelongsTo<Postulante, $this> */
    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class);
    }
}
