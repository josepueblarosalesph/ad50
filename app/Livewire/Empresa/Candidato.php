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

    public bool $puedeVerContacto = false;

    public function mount(BusquedaCandidato $match): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($match->busqueda->empresa_id === auth()->user()->empresa?->id, 403);
        abort_unless($match->postulante->visible, 404);

        $this->match = $match->load('busqueda', 'postulante.user');
        $empresa = auth()->user()->empresa;
        $this->puedeVerContacto = $empresa?->plan_id !== null
            && $empresa->plan_hasta !== null
            && $empresa->plan_hasta->endOfDay()->isFuture();

        if ($this->puedeVerContacto && $this->match->contactado_at === null) {
            $this->match->update(['contactado_at' => now()]);
        }
    }

    #[Title('Ficha de candidato · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.candidato');
    }
}
