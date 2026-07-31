<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota que un usuario de una empresa escribe sobre un postulante.
 *
 * Es de quien la escribe (`user_id`), no de la empresa entera: cada usuario del equipo
 * tiene la suya sobre el mismo candidato —de ahí el único por (empresa, postulante,
 * usuario)— y decide con `visibilidad` si la comparte con su equipo o se la reserva.
 * En ningún caso la ve el postulante ni otra empresa.
 */
class NotaCandidato extends Model
{
    /**
     * Quién puede leer la nota, además de su autor.
     *
     * @var array<string, string>
     */
    public const VISIBILIDADES = [
        'equipo' => 'Todo mi equipo',
        'privada' => 'Solo yo',
    ];

    protected $table = 'notas_candidato';

    protected $guarded = [];

    protected $attributes = [
        'visibilidad' => 'equipo',
    ];

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return BelongsTo<Postulante, $this> */
    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class);
    }

    /**
     * Autor de la nota; null si dejó el equipo y su nota compartida sobrevivió.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function esPrivada(): bool
    {
        return $this->visibilidad === 'privada';
    }

    public function autorLabel(): string
    {
        return $this->user->name ?? 'Usuario eliminado';
    }

    /**
     * Acota a lo que ese usuario tiene derecho a leer: lo que su equipo comparte más lo
     * suyo propio. No filtra por empresa: eso lo decide quien llama, según el contexto.
     *
     * @param  Builder<NotaCandidato>  $query
     * @return Builder<NotaCandidato>
     */
    public function scopeVisiblesPara(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $visibles) => $visibles
            ->where('visibilidad', 'equipo')
            ->orWhere('user_id', $user->id));
    }
}
