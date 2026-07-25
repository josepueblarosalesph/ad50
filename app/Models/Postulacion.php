<?php

namespace App\Models;

use Database\Factories\PostulacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postulacion extends Model
{
    /** @use HasFactory<PostulacionFactory> */
    use HasFactory;

    /** Estados de la postulación en el flujo de revisión de la empresa. */
    public const ESTADOS = [
        'enviada' => 'Enviada',
        'en_revision' => 'En revisión',
        'seleccionada' => 'Seleccionada',
        'descartada' => 'Descartada',
    ];

    protected $table = 'postulaciones';

    protected $fillable = [
        'publicacion_id',
        'postulante_id',
        'respuestas',
        'estado',
    ];

    protected $attributes = [
        'estado' => 'enviada',
    ];

    protected function casts(): array
    {
        return [
            'respuestas' => 'array',
        ];
    }

    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(Publicacion::class);
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class);
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }
}
