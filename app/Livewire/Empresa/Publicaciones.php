<?php

namespace App\Livewire\Empresa;

use App\Models\Publicacion;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Publicaciones extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
    }

    public function cambiarEstado(Publicacion $publicacion, string $estado): void
    {
        abort_unless($publicacion->empresa_id === auth()->user()->empresa?->id, 403);
        abort_unless(in_array($estado, ['publicada', 'pausada', 'cerrada'], true), 422);

        $publicacion->update(['estado' => $estado]);
    }

    #[Title('Publicaciones · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.publicaciones', [
            'publicaciones' => Publicacion::query()
                ->whereBelongsTo(auth()->user()->empresa)
                ->withCount('postulaciones')
                ->latest()
                ->paginate(12),
        ]);
    }
}
