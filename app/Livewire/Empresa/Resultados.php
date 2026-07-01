<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Resultados extends Component
{
    public Busqueda $busqueda;

    public function mount(Busqueda $busqueda): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->busqueda = $busqueda;
    }

    #[Title('Resultados de búsqueda · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.resultados', [
            'candidatos' => $this->busqueda->candidatos()
                ->whereHas('postulante', fn ($query) => $query->where('visible', true))
                ->with('postulante.user')
                ->orderByDesc('criterios_cumplidos')
                ->orderBy('postulante_id')
                ->get(),
        ]);
    }
}
