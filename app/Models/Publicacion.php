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
use Illuminate\Support\Facades\DB;

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
            'competencias' => 'json:unicode',
            'idiomas' => 'json:unicode',
            'preguntas' => 'json:unicode',
            'empleo_inclusivo' => 'boolean',
            'postulacion_facil' => 'boolean',
            'notificar_postulaciones' => 'boolean',
            'evaluacion_online' => 'boolean',
            'evaluacion_manual' => 'boolean',
            'mostrar_sueldo' => 'boolean',
            'vigente_hasta' => 'date',
            // Lo agrega scopeWithCandidatosCount(); Postgres devuelve los count() como texto.
            'candidatos_count' => 'integer',
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

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return HasMany<Postulacion, $this> */
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

    /**
     * @param  Builder<Publicacion>  $query
     * @return Builder<Publicacion>
     */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->whereIn('estado', self::ESTADOS_VISIBLES)
            ->whereDate('vigente_hasta', '>=', today());
    }

    /**
     * Agrega `candidatos_count`: las personas de la publicación, vengan de haber postulado
     * o de que la empresa las agregara a mano desde Prospección de Candidatos.
     *
     * Se cuenta por persona, no por origen: quien postuló y además fue agregado vale uno.
     * Es el mismo criterio del detalle (ver Livewire\Empresa\Postulaciones::candidatos()),
     * incluida la regla de que un agregado que ocultó su ficha deja de listarse —quien
     * postuló, en cambio, sigue contando: esa postulación existe.
     *
     * @param  Builder<Publicacion>  $query
     * @return Builder<Publicacion>
     */
    public function scopeWithCandidatosCount(Builder $query): Builder
    {
        $total = Postulante::query()
            ->selectRaw('count(*)')
            ->where(fn ($persona) => $persona
                ->whereExists(fn ($sub) => $sub
                    ->select(DB::raw(1))
                    ->from('postulaciones')
                    ->whereColumn('postulaciones.publicacion_id', 'publicaciones.id')
                    ->whereColumn('postulaciones.postulante_id', 'postulantes.id'))
                ->orWhere(fn ($agregado) => $agregado
                    ->where('postulantes.visible', true)
                    ->whereExists(fn ($sub) => $sub
                        ->select(DB::raw(1))
                        ->from('publicacion_candidato')
                        ->whereColumn('publicacion_candidato.publicacion_id', 'publicaciones.id')
                        ->whereColumn('publicacion_candidato.postulante_id', 'postulantes.id'))));

        // Un único subselect (y no la suma de dos) para poder ordenar por el alias.
        return $query->select('publicaciones.*')->selectSub($total, 'candidatos_count');
    }
}
