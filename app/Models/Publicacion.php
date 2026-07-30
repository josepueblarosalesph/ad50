<?php

namespace App\Models;

use Database\Factories\PublicacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Atributos con cast declarados para el análisis estático.
 *
 * @property Carbon|null $vigente_hasta
 * @property list<string>|null $competencias
 * @property list<string>|null $idiomas
 * @property list<string>|null $preguntas
 */
class Publicacion extends Model
{
    /** @use HasFactory<PublicacionFactory> */
    use HasFactory;

    use SoftDeletes;

    /** Días que una publicación permanece en papelera antes de eliminarse en forma definitiva. */
    public const DIAS_RETENCION_PAPELERA = 30;

    /**
     * Estado de la publicación: recorre la etapa del proceso de selección, desde que
     * se publica hasta que se cierra. Es un único eje, no dos: la etapa determina
     * también si la oferta sigue visible en el portal (ver ESTADOS_VISIBLES).
     *
     * @var array<string, string>
     */
    public const ESTADOS = [
        'publicada' => 'Publicada',
        'long_list' => 'Long List',
        'short_list' => 'Short List',
        'entrevistas' => 'Entrevistas',
        'pausada' => 'Pausada',
        'cerrada' => 'Cerrada',
        'cancelada' => 'Cancelada',
    ];

    /**
     * Estados en que la oferta sigue publicada para los postulantes. Mientras la
     * empresa avanza por el pipeline la oferta sigue abierta: solo deja de verse al
     * pausarla, cerrarla o cancelarla.
     *
     * @var list<string>
     */
    public const ESTADOS_VISIBLES = ['publicada', 'long_list', 'short_list', 'entrevistas'];

    /**
     * Estados que dan por terminado el proceso: no admiten asociar más candidatos.
     *
     * @var list<string>
     */
    public const ESTADOS_TERMINADOS = ['cerrada', 'cancelada'];

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

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    /** Visible para los postulantes: en una etapa abierta y dentro del período de vigencia. */
    public function estaVigente(): bool
    {
        return in_array($this->estado, self::ESTADOS_VISIBLES, true) && $this->vigente_hasta?->gte(today());
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }

    /**
     * Candidatos que la empresa asoció a esta publicación desde Prospección de Candidatos.
     *
     * @return BelongsToMany<Postulante, $this>
     */
    public function candidatos(): BelongsToMany
    {
        return $this->belongsToMany(Postulante::class, 'publicacion_candidato')
            ->withPivot('busqueda_id')
            ->withTimestamps();
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->whereIn('estado', self::ESTADOS_VISIBLES)
            ->whereDate('vigente_hasta', '>=', today());
    }
}
