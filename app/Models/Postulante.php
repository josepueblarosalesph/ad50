<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postulante extends Model
{
    protected $guarded = [];
    protected $casts = [
        'visible' => 'bool',
        'suscripcion_hasta' => 'date',
        'anio_nacimiento' => 'integer',
        'experiencia_inicio' => 'integer',
        'experiencia_fin' => 'integer',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function matches(): HasMany { return $this->hasMany(BusquedaCandidato::class); }
}
