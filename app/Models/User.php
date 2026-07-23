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
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
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
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            'postulante' => $this->postulante && ! $this->postulante->onboarding_completado ? 'postulante.ficha' : 'postulante.panel',
            'empresa' => $this->empresa?->estaActiva() ? 'empresa.panel' : 'empresa.activacion',
            'admin' => 'admin.panel',
            default => 'dashboard',
        };
    }

    public function dashboardLabel(): string
    {
        return match ($this->role) {
            'postulante' => 'Mi perfil',
            'empresa' => 'Panel de Admin',
            'admin' => 'Panel de Admin',
            default => 'Dashboard',
        };
    }

    public function postulante(): HasOne
    {
        return $this->hasOne(Postulante::class);
    }

    /**
     * Empresa de la que este usuario es contacto principal (dueño), vía empresas.user_id.
     * Se conserva como relación para poder hacer `->empresa()->update(...)`.
     */
    public function empresa(): HasOne
    {
        return $this->hasOne(Empresa::class);
    }

    /** Empresa a la que el usuario fue agregado como miembro adicional, vía users.empresa_id. */
    public function empresaMembresia(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * La empresa efectiva del usuario: la que posee (principal) o, si es un usuario
     * adicional, aquella a la que pertenece. Resolver la del principal por ownership
     * (no por empresa_id) evita depender de que la columna esté cargada en memoria.
     */
    public function getEmpresaAttribute(): ?Empresa
    {
        if (! array_key_exists('empresa', $this->relations)) {
            $this->setRelation('empresa', $this->empresa()->first() ?? $this->empresaMembresia()->first());
        }

        return $this->getRelation('empresa');
    }

    /** Es el contacto principal (dueño) de su empresa. */
    public function esPrincipalEmpresa(): bool
    {
        return $this->role === 'empresa'
            && $this->empresa !== null
            && $this->empresa->user_id === $this->id;
    }
}
