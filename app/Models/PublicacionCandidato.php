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

    /**
     * Arranca en revisión y no en "recibida": a esta persona la empresa la buscó y la
     * eligió a mano, así que ya está mirándola. "Recibida" describe lo que llega solo.
     */
    protected $attributes = [
        'estado' => 'en_revision',
    ];

    /** Comparte el catálogo de estados con la postulación: es el mismo proceso. */
    public function estadoLabel(): string
    {
        return Postulacion::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

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
