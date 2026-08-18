<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $nombres
 * @property string|null $apellidos
 * @property string $email
 * @property string $role
 * @property bool $acepta_ley_21719
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'nombres', 'apellidos', 'email', 'password', 'role', 'empresa_id', 'acepta_ley_21719'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        // Al sacar a alguien del equipo, sus notas privadas se van con él: nadie más
        // podía leerlas. Las que compartió se quedan (con el autor en null) porque ya
        // son parte de lo que el equipo sabe del candidato.
        static::deleting(function (User $user): void {
            NotaCandidato::query()
                ->where('user_id', $user->id)
                ->where('visibilidad', 'privada')
                ->delete();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'acepta_ley_21719' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Roles asignables desde el panel de superadministración, con su etiqueta.
     *
     * Fuente única para el selector de la pantalla de Usuarios y para la validación:
     * así no se puede guardar un rol que la interfaz no ofrece.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'postulante' => 'Postulante',
        'empresa' => 'Empresa',
        'admin' => 'Administrador',
        'superadmin' => 'Superadministrador',
    ];

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            // Sin panel propio, el postulante entra directo a Oportunidades.
            'postulante' => $this->postulante && ! $this->postulante->onboarding_completado ? 'postulante.ficha' : 'postulante.busquedas',
            'empresa' => $this->rutaPanelEmpresa(),
            'admin', 'superadmin' => 'admin.panel',
            default => 'dashboard',
        };
    }

    /**
     * Entrada al panel de empresa según lo que le falte por hacer: pagar el plan, enviar
     * los antecedentes o nada. La comparten el destino tras el login y el conmutador de
     * paneles, para que ambos lleven al mismo sitio y respeten el mismo gating que
     * EnsureEmpresaActiva.
     */
    public function rutaPanelEmpresa(): string
    {
        return match (true) {
            ! ($this->empresa?->planVigente()) => 'empresa.planes',
            ! $this->empresa->datosEnviados() => 'empresa.activacion',
            default => 'empresa.panel',
        };
    }

    public function dashboardLabel(): string
    {
        return match ($this->role) {
            'postulante' => 'Mi perfil',
            'empresa' => 'Panel de Admin',
            'admin', 'superadmin' => 'Panel de Admin',
            default => 'Dashboard',
        };
    }

    /**
     * Tiene acceso a la administración de la plataforma.
     *
     * El superadmin es un admin con atribuciones extra, así que todo lo que hoy protege
     * el rol `admin` se pregunta por aquí: de lo contrario el superadmin quedaría fuera
     * de las pantallas que sí le corresponden.
     */
    public function esAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    /** Puede además ver todas las cuentas y cambiarles el rol. */
    public function esSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /** Nombre del rol para mostrar en pantalla. */
    public function rolLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /** @return HasOne<Postulante, $this> */
    public function postulante(): HasOne
    {
        return $this->hasOne(Postulante::class);
    }

    /**
     * Empresa de la que este usuario es contacto administrador (dueño), vía empresas.user_id.
     * Se conserva como relación para poder hacer `->empresa()->update(...)`.
     *
     * @return HasOne<Empresa, $this>
     */
    public function empresa(): HasOne
    {
        return $this->hasOne(Empresa::class);
    }

    /**
     * Empresa a la que el usuario fue agregado como miembro adicional, vía users.empresa_id.
     *
     * @return BelongsTo<Empresa, $this>
     */
    public function empresaMembresia(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * La empresa efectiva del usuario: la que administra o, si es un contacto usuario,
     * aquella a la que pertenece. Resolver la del administrador por ownership
     * (no por empresa_id) evita depender de que la columna esté cargada en memoria.
     */
    public function getEmpresaAttribute(): ?Empresa
    {
        if (! array_key_exists('empresa', $this->relations)) {
            $this->setRelation('empresa', $this->empresa()->first() ?? $this->empresaMembresia()->first());
        }

        return $this->getRelation('empresa');
    }

    /**
     * Puede operar el panel de empresa.
     *
     * Además del rol `empresa`, lo puede un administrador de la plataforma que tenga una
     * empresa asociada. Ese caso nace de promover a admin a un contacto de empresa: el
     * cambio de rol conserva la empresa intacta (ver Admin\Usuarios::cambiarRol) y la
     * persona sigue necesitando administrarla. Su identidad principal sigue siendo la de
     * admin —ahí lo deja el login, ver dashboardRouteName()—; el panel de empresa es un
     * acceso adicional que alcanza por el conmutador del encabezado.
     *
     * El orden importa: quien es empresa a secas resuelve sin consultar la relación.
     */
    public function esEmpresa(): bool
    {
        return $this->role === 'empresa'
            || ($this->esAdmin() && $this->empresa !== null);
    }

    /** Es el contacto administrador (dueño) de su empresa. */
    public function esPrincipalEmpresa(): bool
    {
        return $this->esEmpresa()
            && $this->empresa !== null
            && $this->empresa->user_id === $this->id;
    }
}
