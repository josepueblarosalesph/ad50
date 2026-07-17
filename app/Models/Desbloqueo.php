<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de un perfil desbloqueado por una empresa. Cada fila consume un desbloqueo
 * del cupo del plan de la empresa y es único por (empresa, postulante).
 */
class Desbloqueo extends Model
{
    protected $table = 'desbloqueos';

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
