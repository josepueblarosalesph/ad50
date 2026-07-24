<?php

namespace App\Livewire\Empresa;

use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\NotaCandidato;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Candidato extends Component
{
    public BusquedaCandidato $match;

    #[Url]
    public string $filtro = 'todos';

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

    public string $nota = '';

    public bool $notaGuardada = false;

    public function mount(BusquedaCandidato $match): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($match->busqueda->empresa_id === auth()->user()->empresa?->id, 403);
        abort_unless($match->postulante->visible, 404);

        $this->filtro = in_array($this->filtro, ['todos', 'favoritos'], true) ? $this->filtro : 'todos';

        $this->match = $match->load('busqueda', 'postulante.user');
        $this->nota = NotaCandidato::query()
            ->where('empresa_id', $match->busqueda->empresa_id)
            ->where('postulante_id', $match->postulante_id)
            ->value('contenido') ?? '';
        $this->criterios = array_values(array_intersect($this->criterios, array_keys($this->criteriosDisponibles())));
        $this->cvDisponible = filled($this->match->postulante->cv_ruta)
            && Storage::disk('local')->exists($this->match->postulante->cv_ruta);
        $this->hidratarAcceso(auth()->user()->empresa);

        $this->cargarNavegacion();
    }

    public function toggleFavorito(): void
    {
        $this->match->update(['favorito' => ! $this->match->favorito]);
        $this->match->refresh();
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

    public function guardarNota(): void
    {
        $validated = $this->validate([
            'nota' => ['nullable', 'string', 'max:2000'],
        ]);

        $clave = [
            'empresa_id' => $this->match->busqueda->empresa_id,
            'postulante_id' => $this->match->postulante_id,
        ];

        if (blank($validated['nota'])) {
            NotaCandidato::query()->where($clave)->delete();
        } else {
            NotaCandidato::query()->updateOrCreate($clave, ['contenido' => $validated['nota']]);
        }

        $this->notaGuardada = true;
    }

    public function updatedNota(): void
    {
        $this->notaGuardada = false;
    }

    public function descargarCv(): StreamedResponse
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
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
        $borrador = $this->borradorFiltros();

        return $borrador !== null
            ? $this->idsNavegacionPrevisualizada($borrador)
            : $this->idsNavegacionGuardada();
    }

    /** @return Collection<int, int> */
    private function idsNavegacionGuardada(): Collection
    {
        $matches = $this->match->busqueda->candidatos()
            ->confirmados()
            ->where('estado_match', 'cumple')
            ->whereHas('postulante', fn ($query) => $query->where('visible', true))
            ->when($this->filtro === 'favoritos', fn ($query) => $query->where('favorito', true))
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
            ->when($this->filtro === 'favoritos', fn ($query) => $query->where('favorito', true))
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
        ]);
    }
}
