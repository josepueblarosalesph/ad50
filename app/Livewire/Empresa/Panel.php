<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Panel extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
    }

    #[Title('Panel de empresa · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $empresa = auth()->user()->empresa;
        $busquedas = Busqueda::query()
            ->withCount(['candidatos' => fn ($query) => $query->confirmados()])
            ->where('empresa_id', $empresa?->id)
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.empresa.panel', [
            'empresa' => $empresa,
            'busquedas' => $busquedas,
            'totalCandidatos' => BusquedaCandidato::query()
                ->confirmados()
                ->whereHas('busqueda', fn ($query) => $query->where('empresa_id', $empresa?->id))
                ->count(),
        ]);
    }
}
