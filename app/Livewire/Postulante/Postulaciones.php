<?php

namespace App\Livewire\Postulante;

use App\Models\Postulacion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Las postulaciones que el postulante envió, con el estado que les dio cada empresa.
 */
class Postulaciones extends Component
{
    use WithPagination;

    /** Filtro por estado: 'todas' o una clave de Postulacion::ESTADOS. */
    #[Url(history: true)]
    public string $estado = 'todas';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'postulante', 403);

        if ($this->estado !== 'todas' && ! array_key_exists($this->estado, Postulacion::ESTADOS)) {
            $this->estado = 'todas';
        }
    }

    public function mostrar(string $estado): void
    {
        abort_unless($estado === 'todas' || array_key_exists($estado, Postulacion::ESTADOS), 404);

        $this->estado = $estado;
        $this->resetPage(pageName: 'postulaciones');
    }

    #[Title('Mis postulaciones · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $postulanteId = auth()->user()->postulante?->id;

        $base = Postulacion::query()->where('postulante_id', $postulanteId);

        /** @var array<string, int> $conteoPorEstado */
        $conteoPorEstado = (clone $base)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $postulaciones = $base
            ->when($this->estado !== 'todas', fn (Builder $query) => $query->where('estado', $this->estado))
            // Con withTrashed la postulación sigue mostrando a qué oferta fue, aunque la
            // empresa haya mandado la publicación a la papelera.
            ->with(['publicacion' => fn ($query) => $query->withTrashed()])
            ->latest()
            ->paginate(10, pageName: 'postulaciones');

        return view('livewire.postulante.postulaciones', [
            'postulaciones' => $postulaciones,
            'estados' => Postulacion::ESTADOS,
            'conteoPorEstado' => $conteoPorEstado,
            'totalPostulaciones' => array_sum($conteoPorEstado),
        ]);
    }
}
