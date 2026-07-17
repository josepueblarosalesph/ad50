<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Desbloqueo;
use App\Models\NotaCandidato;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Resultados extends Component
{
    use WithPagination;

    public Busqueda $busqueda;

    #[Url(history: true)]
    public string $filtro = 'todos';

    /** @var list<string> */
    #[Url(history: true)]
    public array $criterios = [];

    public bool $editandoTitulo = false;

    public string $tituloEditado = '';

    public function mount(Busqueda $busqueda): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->busqueda = $busqueda;
        $this->criterios = array_values(array_intersect($this->criterios, array_keys($this->criteriosDisponibles())));
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

    #[On('criterios-actualizados')]
    public function actualizarResultados(): void
    {
        $this->busqueda->refresh();
        $this->criterios = [];
        $this->resetPage(pageName: 'candidatos');
    }

    #[Title('Resultados de búsqueda · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $query = $this->busqueda->candidatos()
            ->where('estado_match', 'cumple')
            ->whereHas('postulante', fn ($query) => $query->where('visible', true));

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

        return view('livewire.empresa.resultados', [
            'candidatos' => $candidatos,
            'postulantesConNota' => $postulantesConNota,
            'postulantesDesbloqueados' => $postulantesDesbloqueados,
            'totalCandidatos' => $totalCandidatos,
            'totalFavoritos' => $totalFavoritos,
        ]);
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

        return collect($this->busqueda->criterios ?? [])
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
