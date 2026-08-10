<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Agrupación de candidatos guardados, creada por un usuario de una empresa.
 *
 * Es de quien la crea (`user_id`) y no de la empresa entera: el favorito sí es de la
 * cuenta —lo ve todo el equipo— pero cada reclutador organiza esos mismos candidatos a
 * su manera, sin pisarse con sus colegas. Mismo criterio que [NotaCandidato].
 *
 * Un candidato puede estar en varias carpetas: agrupar no es mover.
 */
class CarpetaFavoritos extends Model
{
    /** Tope de carpetas por usuario: la barra lateral deja de ser navegable mucho antes. */
    public const MAXIMO_POR_USUARIO = 30;

    protected $table = 'carpetas_favoritos';

    protected $guarded = [];

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Favorito, $this> */
    public function favoritos(): BelongsToMany
    {
        return $this->belongsToMany(Favorito::class, 'carpeta_favorito', 'carpeta_id', 'favorito_id')
            ->withTimestamps();
    }
}
