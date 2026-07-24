<?php

namespace App\Livewire\Postulante;

use App\Models\BusquedaCandidato;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Busquedas extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'postulante', 403);
    }

    #[Title('Búsquedas que me incluyen · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $postulante = auth()->user()->postulante;

        return view('livewire.postulante.busquedas', [
            'matches' => BusquedaCandidato::query()
                ->confirmados()
                ->whereHas('busqueda')
                ->with('busqueda')
                ->where('postulante_id', $postulante?->id)
                ->latest()
                ->paginate(12),
        ]);
    }
}
