<?php

namespace App\Livewire\Empresa;

use App\Concerns\AsociaCandidatosAPublicaciones;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Favorito;
use App\Models\NotaCandidato;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Candidato extends Component
{
    use AsociaCandidatosAPublicaciones;

    public BusquedaCandidato $match;

    #[Url]
    public string $filtro = 'todos';

    /**
     * Desde dónde se abrió el perfil: 'busqueda' (los resultados de una búsqueda) o
     * 'favoritos' (la lista de la cuenta). Define entre qué candidatos navegan las
     * flechas y a dónde vuelve el enlace de retorno.
     */
    #[Url]
    public string $origen = 'busqueda';

    /** @var list<string> */
    #[Url]
    public array $criterios = [];

    public bool $puedeVerContacto = false;

    public bool $desbloqueado = false;

    public bool $planVigente = false;

    public int $desbloqueosDisponibles = 0;

    public bool $cvDisponible = false;

    public ?int $anteriorId = null;

    public ?int $siguienteId = null;

    public int $posicion = 1;

    public int $totalCandidatos = 1;

    public bool $esFavorito = false;

    /** Nota de quien está mirando: cada usuario del equipo tiene la suya. */
    public string $nota = '';

    /** Quién más puede leerla: `equipo` o `privada` (ver NotaCandidato::VISIBILIDADES). */
    public string $visibilidad = 'equipo';

    public bool $notaGuardada = false;

    public function mount(BusquedaCandidato $match): void
    {
        abort_unless(auth()->user()->esEmpresa(), 403);
        abort_unless($match->busqueda->empresa_id === auth()->user()->empresa?->id, 403);
        abort_unless($match->postulante->visible, 404);

        $this->filtro = in_array($this->filtro, ['todos', 'favoritos'], true) ? $this->filtro : 'todos';
        $this->origen = in_array($this->origen, ['busqueda', 'favoritos'], true) ? $this->origen : 'busqueda';

        // Al venir de la lista de favoritos, el candidato tiene que ser uno de ellos.
        if ($this->origen === 'favoritos') {
            abort_unless(in_array($match->postulante_id, $this->favoritosDeLaEmpresa(), true), 404);
        }

        $this->match = $match->load('busqueda', 'postulante.user');

        $mia = NotaCandidato::query()->where($this->claveDeMiNota())->first();
        $this->nota = $mia->contenido ?? '';
        $this->visibilidad = $mia->visibilidad ?? 'equipo';

        $this->criterios = array_values(array_intersect($this->criterios, array_keys($this->criteriosDisponibles())));
        $this->cvDisponible = filled($this->match->postulante->cv_ruta)
            && Storage::disk('local')->exists($this->match->postulante->cv_ruta);
        $this->esFavorito = auth()->user()->empresa?->haMarcadoFavorito($match->postulante_id) ?? false;
        $this->hidratarAcceso(auth()->user()->empresa);

        $this->cargarNavegacion();
    }

    /** Guarda o quita al candidato de los favoritos de la empresa. */
    public function toggleFavorito(): void
    {
        $empresa = auth()->user()->empresa;

        abort_unless($empresa?->id === $this->match->busqueda->empresa_id, 403);

        // La búsqueda desde la que se está viendo queda como origen del favorito.
        $this->esFavorito = $empresa->alternarFavorito($this->match->postulante_id, $this->match->busqueda_id);
    }

    /**
     * Postulantes guardados por la empresa, para acotar la navegación por favoritos.
     *
     * @return list<int>
     */
    private function favoritosDeLaEmpresa(): array
    {
        return once(fn (): array => array_values(
            Favorito::query()
                ->where('empresa_id', $this->match->busqueda->empresa_id)
                ->pluck('postulante_id')
                ->map(fn ($id): int => (int) $id)
                ->all()
        ));
    }

    /** Desbloquea el perfil consumiendo un cupo del plan de la empresa. */
    public function desbloquear(): void
    {
        $empresa = auth()->user()->empresa;
        abort_unless($empresa !== null && $empresa->id === $this->match->busqueda->empresa_id, 403);

        if ($empresa->haDesbloqueado($this->match->postulante_id)) {
            $this->hidratarAcceso($empresa);

            return;
        }

        if (! $empresa->planVigente()) {
            $this->addError('desbloqueo', 'Necesitas una suscripción activa para desbloquear perfiles.');

            return;
        }

        if ($empresa->desbloqueosDisponibles() < 1) {
            $this->addError('desbloqueo', 'No te quedan desbloqueos disponibles en tu plan.');

            return;
        }

        $empresa->desbloqueos()->create(['postulante_id' => $this->match->postulante_id]);

        if ($this->match->contactado_at === null) {
            $this->match->update(['contactado_at' => now()]);
        }

        $this->hidratarAcceso($empresa->fresh());
    }

    private function hidratarAcceso(?Empresa $empresa): void
    {
        $this->desbloqueado = $empresa !== null && $empresa->haDesbloqueado($this->match->postulante_id);
        $this->puedeVerContacto = $this->desbloqueado;
        $this->planVigente = $empresa !== null && $empresa->planVigente();
        $this->desbloqueosDisponibles = $empresa?->desbloqueosDisponibles() ?? 0;
    }

    protected function empresaDeAsociacion(): ?Empresa
    {
        return auth()->user()->empresa;
    }

    protected function busquedaDeAsociacion(): ?int
    {
        return $this->match->busqueda_id;
    }

    /** Desde el detalle solo se asocia al candidato que se está viendo. */
    protected function candidatoAsociable(int $postulanteId): bool
    {
        return auth()->user()->esEmpresa()
            && auth()->user()->empresa?->id === $this->match->busqueda->empresa_id
            && $postulanteId === $this->match->postulante_id
            && $this->match->postulante->visible;
    }

    public function guardarNota(): void
    {
        $validated = $this->validate([
            'nota' => ['nullable', 'string', 'max:2000'],
            'visibilidad' => ['required', Rule::in(array_keys(NotaCandidato::VISIBILIDADES))],
        ]);

        $clave = $this->claveDeMiNota();

        if (blank($validated['nota'])) {
            NotaCandidato::query()->where($clave)->delete();
        } else {
            NotaCandidato::query()->updateOrCreate($clave, [
                'contenido' => $validated['nota'],
                'visibilidad' => $validated['visibilidad'],
            ]);
        }

        $this->notaGuardada = true;
    }

    public function updatedNota(): void
    {
        $this->notaGuardada = false;
    }

    public function updatedVisibilidad(): void
    {
        $this->notaGuardada = false;
    }

    /**
     * Identifica la nota de quien está mirando: una por empresa, candidato y usuario.
     *
     * @return array<string, int>
     */
    private function claveDeMiNota(): array
    {
        return [
            'empresa_id' => (int) $this->match->busqueda->empresa_id,
            'postulante_id' => (int) $this->match->postulante_id,
            'user_id' => auth()->user()->id,
        ];
    }

    /**
     * Notas de otros usuarios del equipo que compartieron con la empresa. La propia no
     * va aquí: se edita arriba.
     *
     * @return Collection<int, NotaCandidato>
     */
    private function notasDelEquipo(): Collection
    {
        return NotaCandidato::query()
            ->where('empresa_id', $this->match->busqueda->empresa_id)
            ->where('postulante_id', $this->match->postulante_id)
            ->where('visibilidad', 'equipo')
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', '!=', auth()->user()->id))
            ->with('user:id,name')
            ->latest('updated_at')
            ->get();
    }

    public function descargarCv(): StreamedResponse
    {
        abort_unless(auth()->user()->esEmpresa(), 403);
        abort_unless($this->match->busqueda->empresa_id === auth()->user()->empresa?->id, 403);
        abort_unless($this->match->postulante->visible, 404);
        abort_unless(auth()->user()->empresa?->haDesbloqueado($this->match->postulante_id), 403);

        $cvRuta = $this->match->postulante->cv_ruta;

        abort_unless(filled($cvRuta) && Storage::disk('local')->exists($cvRuta), 404);

        return Storage::disk('local')->download(
            $cvRuta,
            'cv-postulante-'.$this->match->postulante_id.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function cambiarFiltro(string $filtro): void
    {
        abort_unless(in_array($filtro, ['todos', 'favoritos'], true), 404);
        // Viniendo de la lista de favoritos no hay filtro que cambiar: el conjunto ya es ese.
        abort_if($this->origen === 'favoritos', 404);

        if ($filtro === $this->filtro) {
            return;
        }

        $this->filtro = $filtro;
        $ids = $this->idsNavegacion();

        // Si el candidato actual no está en el nuevo filtro, saltamos al primero del set (o a resultados).
        if (! $ids->contains($this->match->id)) {
            if ($ids->isEmpty()) {
                $this->redirectRoute('empresa.resultados', ['busqueda' => $this->match->busqueda, 'filtro' => $filtro, 'criterios' => $this->criterios], navigate: true);

                return;
            }

            $this->redirectRoute('empresa.candidatos.show', ['match' => $ids->first(), 'filtro' => $filtro, 'criterios' => $this->criterios], navigate: true);

            return;
        }

        $this->cargarNavegacion();
    }

    /**
     * Conjunto navegable de candidatos. Si hay un borrador de filtros sin guardar
     * (previsualización), se recorre ese mismo conjunto para que la posición y el
     * total coincidan con lo que muestra el listado; si no, el conjunto guardado.
     *
     * @return Collection<int, int>
     */
    private function idsNavegacion(): Collection
    {
        if ($this->origen === 'favoritos') {
            return $this->idsNavegacionFavoritos();
        }

        $borrador = $this->borradorFiltros();

        return $borrador !== null
            ? $this->idsNavegacionPrevisualizada($borrador)
            : $this->idsNavegacionGuardada();
    }

    /**
     * Navegación entre los favoritos de la cuenta, sin importar de qué búsqueda venga
     * cada uno. Se toma una coincidencia por candidato —la lista de favoritos enlaza
     * igual— y se respeta su mismo orden (por postulante).
     *
     * @return Collection<int, int>
     */
    private function idsNavegacionFavoritos(): Collection
    {
        $empresaId = $this->match->busqueda->empresa_id;

        return BusquedaCandidato::query()
            ->confirmados()
            ->whereIn('postulante_id', $this->favoritosDeLaEmpresa())
            ->whereHas('busqueda', fn ($query) => $query->where('empresa_id', $empresaId))
            ->whereHas('postulante', fn ($query) => $query->where('visible', true))
            ->orderBy('postulante_id')
            ->get(['id', 'postulante_id'])
            // Un candidato puede calzar con varias búsquedas: basta una para abrirlo.
            ->unique('postulante_id')
            ->pluck('id')
            ->values();
    }

    /** @return Collection<int, int> */
    private function idsNavegacionGuardada(): Collection
    {
        $matches = $this->match->busqueda->candidatos()
            ->confirmados()
            ->where('estado_match', 'cumple')
            ->whereHas('postulante', fn ($query) => $query->where('visible', true))
            ->when($this->filtro === 'favoritos', fn ($query) => $query->whereIn('postulante_id', $this->favoritosDeLaEmpresa()))
            ->orderByDesc('criterios_cumplidos')
            ->orderBy('postulante_id')
            ->get(['id', 'criterios_detalle']);

        if ($this->criterios !== []) {
            $matches = $matches->filter(fn (BusquedaCandidato $match): bool => $this->cumpleCriterios($match));
        }

        return $matches->pluck('id')->values();
    }

    /**
     * Navegación sobre los perfiles ya guardados que además cumplen el borrador en
     * previsualización, evaluados al vuelo. Mismo orden que el listado previsualizado
     * (por postulante_id). Solo estos perfiles son abribles desde el listado.
     *
     * @param  array<string, mixed>  $criterios
     * @return Collection<int, int>
     */
    private function idsNavegacionPrevisualizada(array $criterios): Collection
    {
        $matching = app(MatchingService::class);

        return $this->match->busqueda->candidatos()
            ->where('estado_match', 'cumple')
            ->whereHas('postulante', fn ($query) => $query->where('visible', true))
            ->when($this->filtro === 'favoritos', fn ($query) => $query->whereIn('postulante_id', $this->favoritosDeLaEmpresa()))
            ->with('postulante')
            ->get()
            ->filter(function (BusquedaCandidato $match) use ($matching, $criterios): bool {
                $detalle = $matching->evaluar($match->postulante, $criterios);

                return ! collect($detalle)->contains(fn (array $criterio): bool => ! ($criterio['cumple'] ?? false));
            })
            ->sortBy('postulante_id')
            ->pluck('id')
            ->values();
    }

    /**
     * Borrador de filtros en previsualización para esta búsqueda, si existe.
     *
     * @return array<string, mixed>|null
     */
    private function borradorFiltros(): ?array
    {
        $borrador = session('filtros_borrador.'.$this->match->busqueda->id);

        return is_array($borrador) ? $borrador : null;
    }

    private function cargarNavegacion(): void
    {
        $ids = $this->idsNavegacion();

        $indice = $ids->search($this->match->id);

        // Si el borrador cambió y el candidato ya no está en el set previsualizado,
        // recurrimos a la navegación guardada para no dejar la vista en 404.
        if ($indice === false && $this->borradorFiltros() !== null) {
            $ids = $this->idsNavegacionGuardada();
            $indice = $ids->search($this->match->id);
        }

        abort_if($indice === false, 404);

        $this->posicion = $indice + 1;
        $this->totalCandidatos = $ids->count();
        $this->anteriorId = $indice > 0 ? $ids[$indice - 1] : null;
        $this->siguienteId = $indice < $ids->count() - 1 ? $ids[$indice + 1] : null;
    }

    /** @return array<string, array{etiqueta: string, valor: mixed}> */
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

        return collect($this->match->busqueda->criterios ?? [])
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

        return collect($this->criterios)->every(fn (string $clave): bool => isset($disponibles[$clave])
            && $detalles->contains(fn (array $detalle): bool => ($detalle['criterio'] ?? null) === $disponibles[$clave]['etiqueta']
                && ($detalle['cumple'] ?? false) === true));
    }

    #[Title('Perfil profesional del candidato · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.candidato', [
            'meses' => CatalogosProfesionales::meses(),
            'criteriosActivos' => collect($this->criteriosDisponibles())->only($this->criterios),
            'publicacionesAsociables' => $this->publicacionesAsociables(),
            'publicacionesDelCandidato' => $this->publicacionesDelCandidato(),
            'totalAsociaciones' => $this->conteoAsociaciones([$this->match->postulante_id])[$this->match->postulante_id] ?? 0,
            'notasDelEquipo' => $this->notasDelEquipo(),
            'visibilidades' => NotaCandidato::VISIBILIDADES,
        ]);
    }
}
