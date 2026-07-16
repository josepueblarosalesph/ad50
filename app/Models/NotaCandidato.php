<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota privada de una empresa sobre un postulante. Es única por (empresa, postulante)
 * y persiste independiente de las búsquedas: la ve solo esa empresa.
 */
class NotaCandidato extends Model
{
    protected $table = 'notas_candidato';

    protected $guarded = [];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class);
    }
}
