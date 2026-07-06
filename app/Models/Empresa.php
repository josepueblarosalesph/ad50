<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $guarded = [];

    protected $casts = [
        'plan_hasta' => 'date',
        'datos_enviados_at' => 'datetime',
        'activada_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function activadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activada_por');
    }

    public function estaActiva(): bool
    {
        return $this->estado_activacion === 'activa';
    }

    public function busquedas(): HasMany
    {
        return $this->hasMany(Busqueda::class);
    }
}
