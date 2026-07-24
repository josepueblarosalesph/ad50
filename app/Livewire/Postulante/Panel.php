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
            ->confirmados()
            ->where('postulante_id', $postulante?->id)
            ->latest()
            ->take(3)
            ->get();

        $totalMatches = BusquedaCandidato::query()
            ->confirmados()
            ->where('postulante_id', $postulante?->id)
            ->count();

        $empresasInteresadas = BusquedaCandidato::query()
            ->confirmados()
            ->join('busquedas', 'busquedas.id', '=', 'busqueda_candidato.busqueda_id')
            ->where('busqueda_candidato.postulante_id', $postulante?->id)
            ->where('busqueda_candidato.favorito', true)
            ->distinct()
            ->count('busquedas.empresa_id');

        return view('livewire.postulante.panel', [
            'postulante' => $postulante,
            'matches' => $matches,
            'totalMatches' => $totalMatches,
            'empresasInteresadas' => $empresasInteresadas,
        ]);
    }
}
