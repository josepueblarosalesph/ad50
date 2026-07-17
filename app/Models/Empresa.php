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

    public function desbloqueos(): HasMany
    {
        return $this->hasMany(Desbloqueo::class);
    }

    /** El plan está vigente (permite desbloquear perfiles). */
    public function planVigente(): bool
    {
        return $this->plan_id !== null
            && $this->plan_hasta !== null
            && $this->plan_hasta->endOfDay()->isFuture();
    }

    public function desbloqueosTotales(): int
    {
        return (int) ($this->plan?->desbloqueos ?? 0);
    }

    public function desbloqueosUsados(): int
    {
        return $this->desbloqueos()->count();
    }

    public function desbloqueosDisponibles(): int
    {
        return max(0, $this->desbloqueosTotales() - $this->desbloqueosUsados());
    }

    public function haDesbloqueado(int $postulanteId): bool
    {
        return $this->desbloqueos()->where('postulante_id', $postulanteId)->exists();
    }
}
