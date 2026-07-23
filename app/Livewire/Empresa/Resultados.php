<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Desbloqueo;
use App\Models\NotaCandidato;
use App\Models\Postulante;
use App\Services\MatchingService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Resultados extends Component
{
    use WithPagination;

    public Busqueda $busqueda;

    #[Url(history: true)]
    public string $filtro = 'todos';

    /**
     * Filtro por antigüedad de la última actualización de la ficha del postulante.
     * Valores: todas | mes | 1a3 | 3a6 | mas6.
     */
    #[Url(history: true)]
    public string $actualizacion = 'todas';

    /** @var list<string> */
    #[Url(history: true)]
    public array $criterios = [];

    public bool $editandoTitulo = false;

    public string $tituloEditado = '';

    /**
     * Criterios en edición que aún no se guardan. Cuando está presente, el listado se
     * calcula al vuelo contra los postulantes visibles en vez de leer la tabla pivote.
     * No es #[Url], así que un refresco de página descarta la previsualización.
     *
     * @var array<string, mixed>|null
     */
    public ?array $previsualizacion = null;

    public function mount(Busqueda $busqueda): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->busqueda = $busqueda;

        // Restaura la previsualización de filtros sin guardar para que se mantenga al
        // volver al listado. FiltrosBusqueda guarda/limpia este borrador en sesión.
        $borrador = session('filtros_borrador.'.$busqueda->id);

        if (is_array($borrador)) {
            $this->previsualizacion = $borrador;
        }

        $this->criterios = array_values(array_intersect($this->criterios, array_keys($this->criteriosDisponibles())));

        if (! in_array($this->actualizacion, ['todas', 'mes', '1a3', '3a6', 'mas6'], true)) {
            $this->actualizacion = 'todas';
        }
    }

    public function updatedActualizacion(): void
    {
        $this->resetPage(pageName: 'candidatos');
    }

    /**
     * El filtro de antigüedad vive en el menú lateral (FiltroActualizacion) y avisa
     * por evento porque se renderiza fuera del root de este componente.
     */
    #[On('actualizacion-cambiada')]
    public function cambiarActualizacion(string $valor): void
    {
        $this->actualizacion = in_array($valor, ['todas', 'mes', '1a3', '3a6', 'mas6'], true) ? $valor : 'todas';
        $this->resetPage(pageName: 'candidatos');
    }

    public function editarTitulo(): void
    {
        $this->tituloEditado = $this->busqueda->titulo;
        $this->editandoTitulo = true;
    }

    public function guardarTitulo(): void
    {
        $validated = $this->validate([
            'tituloEditado' => ['required', 'string', 'max:180'],
        ]);

        $this->busqueda->update(['titulo' => $validated['tituloEditado']]);
        $this->busqueda->refresh();
        $this->editandoTitulo = false;
    }

    public function cancelarTitulo(): void
    {
        $this->editandoTitulo = false;
        $this->resetErrorBag('tituloEditado');
    }

    public function mostrar(string $filtro): void
    {
        abort_unless(in_array($filtro, ['todos', 'favoritos'], true), 404);

        $this->filtro = $filtro;
        $this->resetPage(pageName: 'candidatos');
    }

    public function toggleFavorito(int $matchId): void
    {
        $match = $this->busqueda->candidatos()->find($matchId);

        abort_if($match === null, 404);

        $match->update(['favorito' => ! $match->favorito]);
    }

    /**
     * Desbloquea un perfil desde el listado consumiendo un cupo del plan.
     * Espeja la lógica de Candidato::desbloquear; los errores se informan por flash.
     */
    public function desbloquear(int $postulanteId): void
    {
        $empresa = auth()->user()->empresa;

        abort_unless(auth()->user()->role === 'empresa' && $empresa?->id === $this->busqueda->empresa_id, 403);

        $match = $this->busqueda->candidatos()->where('postulante_id', $postulanteId)->first();

        abort_if($match === null, 404);

        if ($empresa->haDesbloqueado($postulanteId)) {
            return;
        }

        if (! $empresa->planVigente()) {
            session()->flash('desbloqueo_error', 'Necesitas una suscripción activa para desbloquear perfiles.');

            return;
        }

        if ($empresa->desbloqueosDisponibles() < 1) {
            session()->flash('desbloqueo_error', 'No te quedan desbloqueos disponibles en tu plan.');

            return;
        }

        $empresa->desbloqueos()->create(['postulante_id' => $postulanteId]);

        if ($match->contactado_at === null) {
            $match->update(['contactado_at' => now()]);
        }
    }

    /**
     * Descarga rápida del CV desde el listado. Solo para candidatos desbloqueados
     * cuyo perfil pertenece a esta búsqueda; espeja la validación de Candidato::descargarCv.
     */
    public function descargarCv(int $postulanteId): StreamedResponse
    {
        $empresa = auth()->user()->empresa;

        abort_unless(auth()->user()->role === 'empresa' && $empresa?->id === $this->busqueda->empresa_id, 403);
        abort_unless($empresa->haDesbloqueado($postulanteId), 403);

        $match = $this->busqueda->candidatos()
            ->where('postulante_id', $postulanteId)
            ->with('postulante')
            ->first();

        abort_if($match === null, 404);
        abort_unless($match->postulante->visible, 404);

        $cvRuta = $match->postulante->cv_ruta;

        abort_unless(filled($cvRuta) && Storage::disk('local')->exists($cvRuta), 404);

        return Storage::disk('local')->download(
            $cvRuta,
            'cv-postulante-'.$postulanteId.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function toggleCriterio(string $criterio): void
    {
        abort_unless(array_key_exists($criterio, $this->criteriosDisponibles()), 404);

        if (in_array($criterio, $this->criterios, true)) {
            $this->criterios = array_values(array_diff($this->criterios, [$criterio]));
        } else {
            $this->criterios[] = $criterio;
        }

        $this->resetPage(pageName: 'candidatos');
    }

    public function limpiarCriterios(): void
    {
        $this->criterios = [];
        $this->resetPage(pageName: 'candidatos');
    }

    /**
     * @param  array<string, mixed>  $criterios
     */
    #[On('criterios-previsualizados')]
    public function previsualizar(array $criterios): void
    {
        $this->previsualizacion = $criterios;
        $this->criterios = [];
        $this->resetPage(pageName: 'candidatos');
    }

    #[On('criterios-guardados')]
    public function actualizarResultados(): void
    {
        $this->previsualizacion = null;
        $this->busqueda->refresh();
        $this->criterios = [];
        $this->resetPage(pageName: 'candidatos');
    }

    #[Title('Resultados de búsqueda · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        [$candidatos, $totalCandidatos, $totalFavoritos] = $this->previsualizacion !== null
            ? $this->listadoPrevisualizado()
            : $this->listadoGuardado();

        $idsPagina = $candidatos->pluck('postulante_id');

        $postulantesConNota = NotaCandidato::query()
            ->where('empresa_id', $this->busqueda->empresa_id)
            ->whereIn('postulante_id', $idsPagina)
            ->pluck('postulante_id')
            ->all();

        $postulantesDesbloqueados = Desbloqueo::query()
            ->where('empresa_id', $this->busqueda->empresa_id)
            ->whereIn('postulante_id', $idsPagina)
            ->pluck('postulante_id')
            ->all();

        // Solo se comprueba la existencia física del CV de los perfiles desbloqueados
        // de esta página, que son los únicos que muestran el botón de descarga.
        $postulantesConCv = $candidatos->getCollection()
            ->filter(fn (BusquedaCandidato $match): bool => in_array($match->postulante_id, $postulantesDesbloqueados, true)
                && filled($match->postulante->cv_ruta)
                && Storage::disk('local')->exists($match->postulante->cv_ruta))
            ->pluck('postulante_id')
            ->all();

        $empresa = auth()->user()->empresa;

        return view('livewire.empresa.resultados', [
            'candidatos' => $candidatos,
            'postulantesConNota' => $postulantesConNota,
            'postulantesDesbloqueados' => $postulantesDesbloqueados,
            'postulantesConCv' => $postulantesConCv,
            'planVigente' => $empresa?->planVigente() ?? false,
            'desbloqueosDisponibles' => $empresa?->desbloqueosDisponibles() ?? 0,
            'totalCandidatos' => $totalCandidatos,
            'totalFavoritos' => $totalFavoritos,
            'previsualizando' => $this->previsualizacion !== null,
        ]);
    }

    /**
     * Listado a partir de las coincidencias ya materializadas en la tabla pivote.
     *
     * @return array{0: LengthAwarePaginator<int, BusquedaCandidato>, 1: int, 2: int}
     */
    private function listadoGuardado(): array
    {
        $query = $this->busqueda->candidatos()
            ->where('estado_match', 'cumple')
            ->whereHas('postulante', function ($query): void {
                $query->where('visible', true);
                $this->aplicarRangoActualizacion($query);
            });

        $totalCandidatos = (clone $query)->count();
        $totalFavoritos = (clone $query)->where('favorito', true)->count();

        if ($this->criterios !== []) {
            $candidatosFiltrados = (clone $query)
                ->get(['id', 'criterios_detalle'])
                ->filter(fn (BusquedaCandidato $match): bool => $this->cumpleCriterios($match))
                ->pluck('id');

            $query->whereKey($candidatosFiltrados);
        }

        $candidatos = $query
            ->when($this->filtro === 'favoritos', fn ($query) => $query->where('favorito', true))
            ->with('postulante.user')
            ->orderByDesc('criterios_cumplidos')
            ->orderBy('postulante_id')
            ->paginate(20, pageName: 'candidatos');

        return [$candidatos, $totalCandidatos, $totalFavoritos];
    }

    /**
     * Listado de los criterios en edición, evaluado en memoria y sin escribir nada.
     * Reutiliza la fila pivote existente cuando la hay (conserva favorito y el enlace
     * al perfil); los candidatos nuevos se representan con un modelo sin persistir.
     *
     * @return array{0: LengthAwarePaginator<int, BusquedaCandidato>, 1: int, 2: int}
     */
    private function listadoPrevisualizado(): array
    {
        $matching = app(MatchingService::class);
        $criterios = $this->previsualizacion ?? [];
        $existentes = $this->busqueda->candidatos()->get()->keyBy('postulante_id');

        /** @var Collection<int, BusquedaCandidato> $coincidencias */
        $coincidencias = collect();

        Postulante::query()
            ->where('visible', true)
            ->with('user')
            ->chunkById(500, function (Collection $postulantes) use ($matching, $criterios, $existentes, $coincidencias): void {
                foreach ($postulantes as $postulante) {
                    $detalle = $matching->evaluar($postulante, $criterios);

                    if (collect($detalle)->contains(fn (array $criterio): bool => ! $criterio['cumple'])) {
                        continue;
                    }

                    $match = $existentes->get($postulante->id) ?? new BusquedaCandidato([
                        'busqueda_id' => $this->busqueda->id,
                        'postulante_id' => $postulante->id,
                        'favorito' => false,
                    ]);

                    $match->criterios_detalle = array_values($detalle);
                    $match->setRelation('postulante', $postulante);

                    $coincidencias->push($match);
                }
            });

        $coincidencias = $coincidencias
            ->filter(fn (BusquedaCandidato $match): bool => $this->enRangoActualizacion($match->postulante))
            ->values();

        $totalCandidatos = $coincidencias->count();
        $totalFavoritos = $coincidencias->where('favorito', true)->count();

        $visibles = $coincidencias
            ->when($this->criterios !== [], fn (Collection $items): Collection => $items->filter(fn (BusquedaCandidato $match): bool => $this->cumpleCriterios($match)))
            ->when($this->filtro === 'favoritos', fn (Collection $items): Collection => $items->where('favorito', true))
            ->sortBy('postulante_id')
            ->values();

        $pagina = Paginator::resolveCurrentPage('candidatos');

        $candidatos = new LengthAwarePaginator(
            $visibles->forPage($pagina, 20),
            $visibles->count(),
            20,
            $pagina,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'candidatos'],
        );

        return [$candidatos, $totalCandidatos, $totalFavoritos];
    }

    /**
     * Límites [desde, hasta) de updated_at para el filtro de antigüedad de ficha.
     * Cualquiera de los dos extremos puede ser null (sin cota). Null total = sin filtro.
     *
     * @return array{0: Carbon|null, 1: Carbon|null}|null
     */
    private function rangoActualizacion(): ?array
    {
        return match ($this->actualizacion) {
            'mes' => [now()->subMonth(), null],
            '1a3' => [now()->subMonths(3), now()->subMonth()],
            '3a6' => [now()->subMonths(6), now()->subMonths(3)],
            'mas6' => [null, now()->subMonths(6)],
            default => null,
        };
    }

    /**
     * Aplica el rango de antigüedad a una consulta sobre postulantes.
     *
     * @param  Builder  $query
     */
    private function aplicarRangoActualizacion($query): void
    {
        $rango = $this->rangoActualizacion();

        if ($rango === null) {
            return;
        }

        [$desde, $hasta] = $rango;

        if ($desde !== null) {
            $query->where('updated_at', '>=', $desde);
        }

        if ($hasta !== null) {
            $query->where('updated_at', '<', $hasta);
        }
    }

    /**
     * Versión en memoria del rango para el listado previsualizado.
     */
    private function enRangoActualizacion(Postulante $postulante): bool
    {
        $rango = $this->rangoActualizacion();

        if ($rango === null) {
            return true;
        }

        [$desde, $hasta] = $rango;
        $actualizado = $postulante->updated_at;

        if ($actualizado === null) {
            return false;
        }

        return ($desde === null || $actualizado->greaterThanOrEqualTo($desde))
            && ($hasta === null || $actualizado->lessThan($hasta));
    }

    /**
     * Los criterios que se están mostrando: los del borrador si hay previsualización.
     *
     * @return array<string, mixed>
     */
    private function criteriosVigentes(): array
    {
        return $this->previsualizacion ?? $this->busqueda->criterios ?? [];
    }

    /**
     * @return array<string, array{etiqueta: string, valor: mixed}>
     */
    private function criteriosDisponibles(): array
    {
        $etiquetas = [
            'cargo' => 'Cargo',
            'carrera' => 'Carrera o título',
            'especialidad' => 'Especialidad / área',
            'industria' => 'Industria',
            'ciudad' => 'Región',
            'situacion_laboral' => 'Situación laboral',
            'genero' => 'Género',
            'nivel_estudios' => 'Nivel de estudios',
            'situacion_estudios' => 'Situación de estudios',
            'idioma' => 'Idioma',
            'actividad_economica' => 'Actividad económica',
            'institucion' => 'Institución de estudio',
            'empresa' => 'Empresa',
            'min_anios' => 'Experiencia mínima',
            'renta_max' => 'Expectativa de renta',
            'palabra_clave' => 'Palabra clave',
        ];

        return collect($this->criteriosVigentes())
            ->filter(fn (mixed $valor, string $clave): bool => filled($valor) && ! (in_array($clave, ['min_anios', 'renta_max'], true) && (int) $valor === 0))
            ->mapWithKeys(fn (mixed $valor, string $clave): array => isset($etiquetas[$clave]) ? [$clave => [
                'etiqueta' => $etiquetas[$clave],
                'valor' => match ($clave) {
                    'min_anios' => $valor.' años',
                    'renta_max' => 'hasta $'.number_format((int) $valor, 0, ',', '.'),
                    default => is_array($valor) ? implode(', ', $valor) : $valor,
                },
            ]] : [])
            ->all();
    }

    private function cumpleCriterios(BusquedaCandidato $match): bool
    {
        $disponibles = $this->criteriosDisponibles();
        $detalles = collect($match->criterios_detalle ?? []);

        return collect($this->criterios)->every(function (string $clave) use ($detalles, $disponibles): bool {
            $criterio = $disponibles[$clave] ?? null;

            return $criterio !== null && $detalles->contains(fn (array $detalle): bool => ($detalle['criterio'] ?? null) === $criterio['etiqueta']
                && ($detalle['valor'] ?? null) === (string) $criterio['valor']
                && ($detalle['cumple'] ?? false) === true);
        });
    }
}
