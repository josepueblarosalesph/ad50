<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    /** Máximo de contactos usuarios adicionales que el contacto administrador puede sumar. */
    public const MAX_USUARIOS_ADICIONALES = 3;

    protected $guarded = [];

    protected $casts = [
        'plan_hasta' => 'date',
        'datos_enviados_at' => 'datetime',
        'activada_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // El contacto administrador (dueño) pertenece a su propia empresa: al crearla
        // enlazamos su users.empresa_id si aún no lo tiene.
        static::created(function (Empresa $empresa): void {
            if ($empresa->user_id !== null) {
                User::query()
                    ->whereKey($empresa->user_id)
                    ->whereNull('empresa_id')
                    ->update(['empresa_id' => $empresa->id]);
            }
        });
    }

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

    /** La empresa ya completó y envió sus datos de activación. */
    public function datosEnviados(): bool
    {
        return $this->datos_enviados_at !== null;
    }

    /**
     * Puede operar el panel: onboarding completo = datos enviados + plan pagado vigente.
     * El pago es el requisito de acceso (autoservicio, sin aprobación manual).
     */
    public function puedeOperar(): bool
    {
        return $this->datosEnviados() && $this->planVigente();
    }

    public function busquedas(): HasMany
    {
        return $this->hasMany(Busqueda::class);
    }

    public function publicaciones(): HasMany
    {
        return $this->hasMany(Publicacion::class);
    }

    /** Todos los usuarios del equipo (principal + adicionales). */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Contactos usuarios, es decir todos menos el contacto administrador. */
    public function usuariosAdicionales(): HasMany
    {
        return $this->usuarios()->where('id', '!=', $this->user_id);
    }

    public function usuariosAdicionalesDisponibles(): int
    {
        return max(0, self::MAX_USUARIOS_ADICIONALES - $this->usuariosAdicionales()->count());
    }

    public function puedeAgregarUsuario(): bool
    {
        return $this->usuariosAdicionalesDisponibles() > 0;
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
