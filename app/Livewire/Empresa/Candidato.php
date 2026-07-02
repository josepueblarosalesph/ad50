<?php

namespace App\Livewire\Empresa;

use App\Models\BusquedaCandidato;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

class Candidato extends Component
{
    public BusquedaCandidato $match;

    #[Url]
    public string $filtro = 'todos';

    public bool $puedeVerContacto = false;

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
        $empresa = auth()->user()->empresa;
        $this->puedeVerContacto = $empresa?->plan_id !== null
            && $empresa->plan_hasta !== null
            && $empresa->plan_hasta->endOfDay()->isFuture();

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

    private function cargarNavegacion(): void
    {
        $ids = $this->match->busqueda->candidatos()
            ->whereHas('postulante', fn ($query) => $query->where('visible', true))
            ->when($this->filtro === 'favoritos', fn ($query) => $query->where('favorito', true))
            ->orderByDesc('criterios_cumplidos')
            ->orderBy('postulante_id')
            ->pluck('id');

        $indice = $ids->search($this->match->id);

        abort_if($indice === false, 404);

        $this->posicion = $indice + 1;
        $this->totalCandidatos = $ids->count();
        $this->anteriorId = $indice > 0 ? $ids[$indice - 1] : null;
        $this->siguienteId = $indice < $ids->count() - 1 ? $ids[$indice + 1] : null;
    }

    #[Title('Ficha de candidato · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.candidato', [
            'meses' => CatalogosProfesionales::meses(),
        ]);
    }
}
