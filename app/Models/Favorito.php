<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Candidato guardado por una empresa.
 *
 * Es de la cuenta, no de una búsqueda: se marca una vez por candidato y sobrevive a
 * que la búsqueda desde la que se marcó se edite o se elimine. `busqueda_id` queda
 * solo como trazabilidad del origen.
 */
class Favorito extends Model
{
    protected $table = 'favoritos';

    protected $guarded = [];

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return BelongsTo<Postulante, $this> */
    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class);
    }

    /**
     * Búsqueda desde la que se guardó (solo trazabilidad).
     *
     * @return BelongsTo<Busqueda, $this>
     */
    public function busqueda(): BelongsTo
    {
        return $this->belongsTo(Busqueda::class);
    }

    /**
     * Carpetas en las que está agrupado. Son de cada usuario del equipo, así que al
     * consultarlas hay que acotar por `carpetas_favoritos.user_id`.
     *
     * @return BelongsToMany<CarpetaFavoritos, $this>
     */
    public function carpetas(): BelongsToMany
    {
        return $this->belongsToMany(CarpetaFavoritos::class, 'carpeta_favorito', 'favorito_id', 'carpeta_id')
            ->withTimestamps();
    }
}
