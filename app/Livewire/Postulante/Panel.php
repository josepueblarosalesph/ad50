<?php

namespace App\Livewire\Postulante;

use App\Concerns\PostulaAOfertas;
use App\Models\Publicacion;
use App\Services\MatchingService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Panel extends Component
{
    use PostulaAOfertas;

    /** Ofertas que se listan en el panel; el resto queda en Oportunidades. */
    private const OFERTAS_EN_PANEL = 6;

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
        $postulante = auth()->user()->postulante;

        return view('livewire.postulante.panel', [
            'postulante' => $postulante,
            'publicaciones' => Publicacion::query()
                ->vigentes()
                // La fecha hace las veces de marca de "ya postulé": null = todavía no.
                ->withMax(
                    ['postulaciones as postulada_en' => fn (Builder $query) => $query->where('postulante_id', $postulante?->id)],
                    'created_at'
                )
                ->withCasts(['postulada_en' => 'datetime'])
                ->latest()
                ->take(self::OFERTAS_EN_PANEL)
                ->get(),
            'publicacionSeleccionada' => $this->publicacionEnPostulacion(),
        ]);
    }
}
