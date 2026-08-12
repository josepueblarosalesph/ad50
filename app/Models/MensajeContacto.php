<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mensaje que un usuario envía a la administración desde la pantalla de Ayuda.
 *
 * Guarda copia del nombre y el correo de quien escribe: si esa cuenta desaparece o
 * cambia de email, la administración todavía puede saber quién fue y responderle.
 */
class MensajeContacto extends Model
{
    /**
     * Motivos ofrecidos en el formulario, en el orden en que se muestran.
     *
     * @var array<string, string>
     */
    public const MOTIVOS = [
        'servicios' => 'Consultas sobre los servicios',
        'soporte' => 'Soporte técnico',
        'otras' => 'Otras consultas',
    ];

    /**
     * Estados de la bandeja. `respondido` es el único que cierra el mensaje.
     *
     * @var array<string, string>
     */
    public const ESTADOS = [
        'nuevo' => 'Sin leer',
        'leido' => 'Leído',
        'respondido' => 'Respondido',
    ];

    protected $table = 'mensajes_contacto';

    protected $guarded = [];

    protected $attributes = [
        'estado' => 'nuevo',
    ];

    protected $casts = [
        'respondido_at' => 'datetime',
    ];

    /**
     * Autor del mensaje; null si la cuenta se eliminó después de escribirlo.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function motivoLabel(): string
    {
        return self::MOTIVOS[$this->motivo] ?? $this->motivo;
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function estaPendiente(): bool
    {
        return $this->estado !== 'respondido';
    }

    /**
     * @param  Builder<MensajeContacto>  $query
     * @return Builder<MensajeContacto>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', '!=', 'respondido');
    }
}
