<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
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

    public function mount(Busqueda $busqueda): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->busqueda = $busqueda;
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

    #[Title('Resultados de búsqueda · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $query = $this->busqueda->candidatos()
            ->whereHas('postulante', fn ($query) => $query->where('visible', true));

        $totalCandidatos = (clone $query)->count();
        $totalFavoritos = (clone $query)->where('favorito', true)->count();

        return view('livewire.empresa.resultados', [
            'candidatos' => $query
                ->when($this->filtro === 'favoritos', fn ($query) => $query->where('favorito', true))
                ->with('postulante.user')
                ->orderByDesc('criterios_cumplidos')
                ->orderBy('postulante_id')
                ->paginate(20, pageName: 'candidatos'),
            'totalCandidatos' => $totalCandidatos,
            'totalFavoritos' => $totalFavoritos,
        ]);
    }
}
