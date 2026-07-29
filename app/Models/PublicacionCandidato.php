<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Candidato de Prospección de Candidatos asociado a una publicación.
 *
 * Es una asociación manual del reclutador, independiente del matching: sobrevive a que
 * el candidato deje de cumplir los criterios de la búsqueda desde la que se asoció.
 */
class PublicacionCandidato extends Model
{
    protected $table = 'publicacion_candidato';

    protected $guarded = [];

    /** @return BelongsTo<Publicacion, $this> */
    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(Publicacion::class);
    }

    /** @return BelongsTo<Postulante, $this> */
    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class);
    }

    /**
     * Búsqueda desde la que se asoció el candidato (solo trazabilidad).
     *
     * @return BelongsTo<Busqueda, $this>
     */
    public function busqueda(): BelongsTo
    {
        return $this->belongsTo(Busqueda::class);
    }
}
