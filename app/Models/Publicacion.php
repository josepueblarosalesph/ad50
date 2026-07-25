<?php

namespace App\Models;

use Database\Factories\PublicacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publicacion extends Model
{
    /** @use HasFactory<PublicacionFactory> */
    use HasFactory;

    protected $table = 'publicaciones';

    protected $fillable = [
        'empresa_id',
        'cargo',
        'tipo_cargo',
        'vacantes',
        'nombre_empresa',
        'descripcion',
        'modalidad',
        'pais',
        'comuna',
        'actividad_empresa',
        'jerarquia',
        'sueldo',
        'mostrar_sueldo',
        'requisitos',
        'experiencia_laboral',
        'estudios_minimos',
        'situacion_academica',
        'competencias',
        'idiomas',
        'preguntas',
        'empleo_inclusivo',
        'postulacion_facil',
        'notificar_postulaciones',
        'evaluacion_online',
        'evaluacion_manual',
        'vigencia_dias',
        'vigente_hasta',
        'estado',
    ];

    protected $attributes = [
        'estado' => 'publicada',
        'vacantes' => 1,
        'pais' => 'Chile',
        'vigencia_dias' => 30,
        'mostrar_sueldo' => false,
        'empleo_inclusivo' => false,
        'postulacion_facil' => true,
        'notificar_postulaciones' => true,
        'evaluacion_online' => false,
        'evaluacion_manual' => false,
    ];

    protected function casts(): array
    {
        return [
            'competencias' => 'array',
            'idiomas' => 'array',
            'preguntas' => 'array',
            'empleo_inclusivo' => 'boolean',
            'postulacion_facil' => 'boolean',
            'notificar_postulaciones' => 'boolean',
            'evaluacion_online' => 'boolean',
            'evaluacion_manual' => 'boolean',
            'mostrar_sueldo' => 'boolean',
            'vigente_hasta' => 'date',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->where('estado', 'publicada')
            ->whereDate('vigente_hasta', '>=', today());
    }
}
