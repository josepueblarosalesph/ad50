<?php

namespace App\Livewire\Empresa;

use App\Models\BusquedaCandidato;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
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

    public bool $cvDisponible = false;

    public ?int $anteriorId = null;

    public ?int $siguienteId = null;

    public int $posicion = 1;

    public int $totalCandidatos = 1;

    public function mount(BusquedaCandidato $match): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($match->busqueda->empresa_id === auth()->user()->empresa?->id, 403);
        abort_unless($match->postulante->visible, 404);

        $this->filtro = in_array($this->filtro, ['todos', 'favoritos'], true) ? $this->filtro : 'todos';

        $this->match = $match->load('busqueda', 'postulante.user');
        $this->criterios = array_values(array_intersect($this->criterios, array_keys($this->criteriosDisponibles())));
        $this->puedeVerContacto = $this->empresaTieneAccesoActivo();
        $this->cvDisponible = filled($this->match->postulante->cv_ruta)
            && Storage::disk('local')->exists($this->match->postulante->cv_ruta);

        if ($this->puedeVerContacto && $this->match->contactado_at === null) {
            $this->match->update(['contactado_at' => now()]);
        }

        $this->cargarNavegacion();
    }

    public function toggleFavorito(): void
    {
        $this->match->update(['favorito' => ! $this->match->favorito]);
        $this->match->refresh();
    }

    public function descargarCv(): StreamedResponse
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($this->match->busqueda->empresa_id === auth()->user()->empresa?->id, 403);
        abort_unless($this->match->postulante->visible, 404);
        abort_unless($this->empresaTieneAccesoActivo(), 403);

        $cvRuta = $this->match->postulante->cv_ruta;

        abort_unless(filled($cvRuta) && Storage::disk('local')->exists($cvRuta), 404);

        return Storage::disk('local')->download(
            $cvRuta,
            'cv-postulante-'.$this->match->postulante_id.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function empresaTieneAccesoActivo(): bool
    {
        $empresa = auth()->user()->empresa;

        return $empresa?->plan_id !== null
            && $empresa->plan_hasta !== null
            && $empresa->plan_hasta->endOfDay()->isFuture();
    }

    private function cargarNavegacion(): void
    {
        $matches = $this->match->busqueda->candidatos()
            ->where('estado_match', 'cumple')
            ->whereHas('postulante', fn ($query) => $query->where('visible', true))
            ->when($this->filtro === 'favoritos', fn ($query) => $query->where('favorito', true))
            ->orderByDesc('criterios_cumplidos')
            ->orderBy('postulante_id')
            ->get(['id', 'criterios_detalle']);

        if ($this->criterios !== []) {
            $matches = $matches->filter(fn (BusquedaCandidato $match): bool => $this->cumpleCriterios($match));
        }

        $ids = $matches->pluck('id')->values();

        $indice = $ids->search($this->match->id);

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
            'cargo' => 'Cargo / especialidad',
            'carrera' => 'Carrera o título',
            'especialidad' => 'Especialidad / área',
            'industria' => 'Industria',
            'ciudad' => 'Ciudad / región',
            'min_anios' => 'Experiencia mínima',
            'palabra_clave' => 'Palabra clave',
        ];

        return collect($this->match->busqueda->criterios ?? [])
            ->filter(fn (mixed $valor, string $clave): bool => filled($valor) && ! ($clave === 'min_anios' && (int) $valor === 0))
            ->mapWithKeys(fn (mixed $valor, string $clave): array => isset($etiquetas[$clave]) ? [$clave => [
                'etiqueta' => $etiquetas[$clave],
                'valor' => $clave === 'min_anios' ? $valor.' años' : (is_array($valor) ? implode(', ', $valor) : $valor),
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

    #[Title('Ficha de candidato · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.candidato', [
            'meses' => CatalogosProfesionales::meses(),
            'criteriosActivos' => collect($this->criteriosDisponibles())->only($this->criterios),
        ]);
    }
}
