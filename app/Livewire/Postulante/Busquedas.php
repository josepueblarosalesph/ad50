<?php

namespace App\Livewire\Postulante;

use App\Concerns\PostulaAOfertas;
use App\Models\Publicacion;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Busquedas extends Component
{
    use PostulaAOfertas;
    use WithPagination;

    /**
     * Filtros de selección múltiple, indexados por la columna de `publicaciones` que
     * acotan. Se llenan solo con valores del catálogo correspondiente (ver opciones()).
     *
     * @var array<string, list<string>>
     */
    public array $seleccion = [
        'modalidad' => [],
        'tipo_cargo' => [],
        'jerarquia' => [],
        'actividad_empresa' => [],
        'experiencia_laboral' => [],
        'estudios_minimos' => [],
        'situacion_academica' => [],
        'idiomas' => [],
    ];

    public string $buscar = '';

    public string $comuna = '';

    public bool $empleoInclusivo = false;

    /**
     * Rango de renta en MILLONES de pesos (un punto del deslizador = $1.000.000).
     * Cubrir todo el recorrido equivale a no filtrar por sueldo.
     */
    public int $sueldoMin = 0;

    public int $sueldoMax = 8;

    /** `idiomas` se guarda como lista JSON; el resto son columnas de texto. */
    private const CAMPO_JSON = 'idiomas';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'postulante', 403);

        $this->sanearSeleccion();
        $this->acotarRangoSueldo();
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'seleccion.')) {
            $this->sanearSeleccion();
        }

        if (in_array($property, ['sueldoMin', 'sueldoMax'], true)) {
            $this->acotarRangoSueldo();
        }

        $this->resetPage();
    }

    /**
     * Pausa o reanuda la visibilidad del perfil. Vive aquí porque Oportunidades es la
     * pantalla de entrada del postulante; al cambiarla se resincroniza el matching.
     */
    public function toggleVisibilidad(MatchingService $matching): void
    {
        $postulante = auth()->user()->postulante;

        abort_if($postulante === null, 404);

        $postulante->visible = ! $postulante->visible;
        $postulante->save();

        $matching->sincronizarPostulante($postulante);
    }

    public function limpiarFiltros(): void
    {
        $limites = CatalogosProfesionales::rangoSueldo();

        $this->seleccion = array_map(fn (): array => [], $this->seleccion);
        $this->buscar = '';
        $this->comuna = '';
        $this->empleoInclusivo = false;
        $this->sueldoMin = $limites['min'];
        $this->sueldoMax = $limites['max'];
        $this->resetPage();
    }

    /**
     * Opciones válidas de cada filtro múltiple. Es la fuente para pintar el panel y
     * también para descartar valores que no vengan del catálogo.
     *
     * @return array<string, array{etiqueta: string, opciones: list<string>}>
     */
    public function opciones(): array
    {
        return [
            'modalidad' => ['etiqueta' => 'Modalidad', 'opciones' => ['Presencial', 'Híbrida', 'Remota']],
            'tipo_cargo' => ['etiqueta' => 'Tipo de jornada', 'opciones' => array_values(CatalogosProfesionales::tiposTrabajo())],
            'jerarquia' => ['etiqueta' => 'Nivel del cargo', 'opciones' => array_values(CatalogosProfesionales::jerarquias())],
            'actividad_empresa' => ['etiqueta' => 'Industria', 'opciones' => array_values(CatalogosProfesionales::industrias())],
            'experiencia_laboral' => ['etiqueta' => 'Experiencia requerida', 'opciones' => array_values(CatalogosProfesionales::rangosExperiencia())],
            'estudios_minimos' => ['etiqueta' => 'Estudios mínimos', 'opciones' => array_values(CatalogosProfesionales::nivelesEstudio())],
            'situacion_academica' => ['etiqueta' => 'Situación académica', 'opciones' => array_values(CatalogosProfesionales::situacionesEstudio())],
            'idiomas' => ['etiqueta' => 'Idiomas', 'opciones' => array_values(CatalogosProfesionales::idiomas())],
        ];
    }

    /** Cuántos filtros hay activos, para el aviso de "limpiar". */
    public function totalFiltrosActivos(): int
    {
        $limites = CatalogosProfesionales::rangoSueldo();

        return collect($this->seleccion)->filter()->count()
            + (filled($this->buscar) ? 1 : 0)
            + (filled($this->comuna) ? 1 : 0)
            + ($this->empleoInclusivo ? 1 : 0)
            + ($this->sueldoMin > $limites['min'] || $this->sueldoMax < $limites['max'] ? 1 : 0);
    }

    /** Descarta valores que no pertenezcan al catálogo del filtro. */
    private function sanearSeleccion(): void
    {
        foreach ($this->opciones() as $campo => ['opciones' => $validas]) {
            $elegidas = (array) ($this->seleccion[$campo] ?? []);

            $this->seleccion[$campo] = array_values(array_intersect($elegidas, $validas));
        }
    }

    /** Mantiene el rango dentro de los límites y con el mínimo por debajo del máximo. */
    private function acotarRangoSueldo(): void
    {
        $limites = CatalogosProfesionales::rangoSueldo();

        $this->sueldoMin = max($limites['min'], min($this->sueldoMin, $limites['max']));
        $this->sueldoMax = max($limites['min'], min($this->sueldoMax, $limites['max']));

        if ($this->sueldoMin > $this->sueldoMax) {
            [$this->sueldoMin, $this->sueldoMax] = [$this->sueldoMax, $this->sueldoMin];
        }
    }

    /**
     * Cada filtro múltiple acota con OR entre sus propios valores y con AND entre
     * filtros distintos, que es como se lee un panel de facetas.
     *
     * @param  Builder<Publicacion>  $query
     */
    private function aplicarSeleccion(Builder $query): void
    {
        foreach ($this->seleccion as $campo => $valores) {
            if ($valores === []) {
                continue;
            }

            if ($campo === self::CAMPO_JSON) {
                // Lista JSON: calza si contiene cualquiera de los idiomas pedidos.
                $query->where(function (Builder $sub) use ($campo, $valores): void {
                    foreach ($valores as $valor) {
                        $sub->orWhereJsonContains($campo, $valor);
                    }
                });

                continue;
            }

            $query->whereIn($campo, $valores);
        }
    }

    /**
     * Aplica el rango de renta. Sin restricción a ninguno de los dos lados no se filtra,
     * para no dejar fuera las ofertas que no informan sueldo; en cuanto se acota, esas
     * quedan excluidas porque no hay forma de saber si calzan.
     *
     * @param  Builder<Publicacion>  $query
     */
    private function aplicarRangoSueldo(Builder $query): void
    {
        $limites = CatalogosProfesionales::rangoSueldo();

        if ($this->sueldoMin <= $limites['min'] && $this->sueldoMax >= $limites['max']) {
            return;
        }

        $query->whereNotNull('sueldo')
            ->where('sueldo', '>=', $this->sueldoMin * CatalogosProfesionales::SUELDO_POR_INTERVALO);

        // El tope superior significa "o más": no se pone cota por arriba.
        if ($this->sueldoMax < $limites['max']) {
            $query->where('sueldo', '<=', $this->sueldoMax * CatalogosProfesionales::SUELDO_POR_INTERVALO);
        }
    }

    #[Title('Oportunidades laborales · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $postulante = auth()->user()->postulante;

        return view('livewire.postulante.busquedas', [
            'postulante' => $postulante,
            'publicaciones' => Publicacion::query()
                ->vigentes()
                // La fecha hace las veces de marca de "ya postulé": null = todavía no.
                ->withMax(
                    ['postulaciones as postulada_en' => fn (Builder $query) => $query->where('postulante_id', $postulante?->id)],
                    'created_at'
                )
                ->withCasts(['postulada_en' => 'datetime'])
                ->when($this->buscar !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query
                        ->whereLike('cargo', '%'.$this->buscar.'%')
                        ->orWhereLike('descripcion', '%'.$this->buscar.'%');
                }))
                ->when($this->comuna !== '', fn (Builder $query) => $query->whereLike('comuna', '%'.$this->comuna.'%'))
                ->when($this->empleoInclusivo, fn (Builder $query) => $query->where('empleo_inclusivo', true))
                ->tap(fn (Builder $query) => $this->aplicarSeleccion($query))
                ->tap(fn (Builder $query) => $this->aplicarRangoSueldo($query))
                ->latest()
                ->paginate(12),
            'filtros' => $this->opciones(),
            'limitesSueldo' => CatalogosProfesionales::rangoSueldo(),
            'filtrosActivos' => $this->totalFiltrosActivos(),
            'publicacionSeleccionada' => $this->publicacionEnPostulacion(),
        ]);
    }
}
