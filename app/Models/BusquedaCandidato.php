<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusquedaCandidato extends Model
{
    protected $table = 'busqueda_candidato';

    protected $guarded = [];

    protected $casts = [
        'favorito' => 'boolean',
        'contactado_at' => 'datetime',
        'criterios_detalle' => 'array',
    ];

    public function busqueda(): BelongsTo
    {
        return $this->belongsTo(Busqueda::class);
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class);
    }
}
