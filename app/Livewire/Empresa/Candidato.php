<?php

namespace App\Livewire\Empresa;

use App\Models\BusquedaCandidato;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Candidato extends Component
{
    public BusquedaCandidato $match;

    public function mount(BusquedaCandidato $match): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($match->busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->match = $match->load('busqueda', 'postulante.user');
    }

    #[Title('Ficha de candidato · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.candidato');
    }
}
