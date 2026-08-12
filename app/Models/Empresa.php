<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
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
        'desbloqueos_cupo' => 'integer',
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Plan, $this> */
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

    /** @return HasMany<Publicacion, $this> */
    public function publicaciones(): HasMany
    {
        return $this->hasMany(Publicacion::class);
    }

    /** @return HasMany<Pago, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
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

    /**
     * Candidatos guardados por la empresa. Es de la cuenta: no depende de la búsqueda
     * desde la que se marcó ni se pierde si esa búsqueda se elimina.
     *
     * @return HasMany<Favorito, $this>
     */
    public function favoritos(): HasMany
    {
        return $this->hasMany(Favorito::class);
    }

    public function haMarcadoFavorito(int $postulanteId): bool
    {
        return $this->favoritos()->where('postulante_id', $postulanteId)->exists();
    }

    /**
     * Marca o desmarca un candidato. Devuelve true si quedó guardado.
     * `$busquedaId` solo registra desde dónde se marcó.
     */
    public function alternarFavorito(int $postulanteId, ?int $busquedaId = null): bool
    {
        $favorito = $this->favoritos()->where('postulante_id', $postulanteId)->first();

        if ($favorito !== null) {
            $favorito->delete();

            return false;
        }

        $this->favoritos()->create([
            'postulante_id' => $postulanteId,
            'busqueda_id' => $busquedaId,
        ]);

        return true;
    }

    /** El plan está vigente (permite desbloquear perfiles). */
    public function planVigente(): bool
    {
        return $this->plan_id !== null
            && $this->plan_hasta !== null
            && $this->plan_hasta->endOfDay()->isFuture();
    }

    /**
     * Cupo de desbloqueos acumulado por todo lo que la empresa ha contratado.
     *
     * Vive en la empresa y no en el plan porque un plan de pago único se puede contratar
     * varias veces y cada compra suma la suya: leerlo del plan daría siempre lo mismo,
     * y volver a comprar no serviría de nada.
     */
    public function desbloqueosTotales(): int
    {
        return (int) $this->desbloqueos_cupo;
    }

    /**
     * Deja a la empresa con el plan contratado: vigencia y cupos, en un solo sitio.
     *
     * Van juntos a propósito. Antes se asignaba `plan_id` por su cuenta y el cupo salía
     * del plan, así que daba igual; ahora el cupo se acumula en la empresa, y separarlos
     * deja a alguien con plan vigente y cero desbloqueos. Lo usan tanto el pago
     * confirmado como la asignación manual desde el panel de admin.
     */
    public function activarPlan(Plan $plan, ?CarbonInterface $hasta = null): void
    {
        $this->update([
            'plan_id' => $plan->id,
            'plan_hasta' => $hasta ?? $plan->vigenciaDesde($this->plan_hasta),
        ]);

        $this->acumularCupos($plan);
    }

    /**
     * Suma a los cupos lo que concede el plan recién pagado. Publicaciones ilimitadas
     * (NULL en el plan) dejan a la empresa ilimitada para siempre: no hay vuelta atrás
     * a un número, porque sería quitarle algo que ya compró.
     */
    public function acumularCupos(Plan $plan): void
    {
        $this->desbloqueos_cupo = $this->desbloqueosTotales() + (int) ($plan->desbloqueos ?? 0);

        if ($plan->publicaciones === null) {
            $this->publicaciones_cupo = null;
        } elseif ($this->publicaciones_cupo !== null) {
            $this->publicaciones_cupo = (int) $this->publicaciones_cupo + (int) $plan->publicaciones;
        }

        $this->save();
    }

    /**
     * Veces que esta empresa contrató (y pagó) ese plan en los últimos 12 meses.
     *
     * La ventana es móvil, no de año calendario: se cuenta hacia atrás desde hoy, así que
     * un cupo se libera al cumplirse el año de la compra que lo ocupaba.
     */
    public function contratacionesUltimoAnio(Plan $plan): int
    {
        return $this->pagos()
            ->where('plan_id', $plan->id)
            ->where('estado', 'pagado')
            ->where('pagado_at', '>=', now()->subYear())
            ->count();
    }

    /** Le quedan contrataciones de ese plan dentro de la ventana de 12 meses. */
    public function puedeContratar(Plan $plan): bool
    {
        return $this->contratacionesRestantes($plan) !== 0;
    }

    /** Contrataciones que le quedan del plan; NULL si no tiene tope. */
    public function contratacionesRestantes(Plan $plan): ?int
    {
        if (! $plan->tieneTopeAnual()) {
            return null;
        }

        return max(0, (int) $plan->max_contrataciones_anuales - $this->contratacionesUltimoAnio($plan));
    }

    /**
     * Cuándo se libera el próximo cupo: al cumplir un año la compra más antigua de las
     * que hoy ocupan el tope. Null si no está al tope.
     */
    public function proximaLiberacionDeCupo(Plan $plan): ?CarbonInterface
    {
        if ($this->contratacionesRestantes($plan) !== 0) {
            return null;
        }

        $masAntigua = $this->pagos()
            ->where('plan_id', $plan->id)
            ->where('estado', 'pagado')
            ->where('pagado_at', '>=', now()->subYear())
            ->orderBy('pagado_at')
            ->value('pagado_at');

        return $masAntigua === null ? null : Carbon::parse($masAntigua)->addYear();
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

    /** Cupo de publicaciones del plan. NULL = ilimitadas. */
    public function publicacionesTotales(): ?int
    {
        return $this->publicaciones_cupo === null ? null : (int) $this->publicaciones_cupo;
    }

    /**
     * Publicaciones creadas contra el cupo. Cuenta también las cerradas y las que están
     * en papelera: el cupo se consume al crear y no se recupera al cerrar ni al eliminar.
     */
    public function publicacionesUsadas(): int
    {
        return $this->publicaciones()->withTrashed()->count();
    }

    /** Publicaciones que aún puede crear. NULL = ilimitadas. */
    public function publicacionesDisponibles(): ?int
    {
        $total = $this->publicacionesTotales();

        return $total === null ? null : max(0, $total - $this->publicacionesUsadas());
    }

    public function tienePublicacionesIlimitadas(): bool
    {
        return $this->publicacionesTotales() === null;
    }

    /** Puede crear una publicación nueva: plan vigente y cupo disponible. */
    public function puedePublicar(): bool
    {
        if (! $this->planVigente()) {
            return false;
        }

        $disponibles = $this->publicacionesDisponibles();

        return $disponibles === null || $disponibles > 0;
    }
}
