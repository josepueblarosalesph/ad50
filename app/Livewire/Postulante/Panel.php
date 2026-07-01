<?php

namespace App\Livewire\Postulante;

use App\Models\BusquedaCandidato;
use App\Services\MatchingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Panel extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'postulante', 403);
    }

    public function toggleVisibilidad(MatchingService $matching): void
    {
        $p = auth()->user()->postulante;
        $p->visible = ! $p->visible;
        $p->save();
        $matching->sincronizarPostulante($p);
    }

    #[Title('Mi panel · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $user = auth()->user();
        $postulante = $user->postulante;

        $matches = BusquedaCandidato::with('busqueda')
            ->where('postulante_id', $postulante?->id)
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.postulante.panel', [
            'postulante' => $postulante,
            'matches' => $matches,
            'totalMatches' => $matches->count(),
        ]);
    }
}
