<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postulante extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'visible' => 'bool',
        'suscripcion_hasta' => 'date',
        'anio_nacimiento' => 'integer',
        'experiencia_inicio' => 'integer',
        'experiencia_fin' => 'integer',
        'educaciones' => 'array',
        'idiomas' => 'array',
        'experiencias' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BusquedaCandidato::class);
    }
}
