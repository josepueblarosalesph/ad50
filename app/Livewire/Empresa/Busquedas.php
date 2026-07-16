<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
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
        abort_unless(auth()->user()->role === 'empresa', 403);
    }

    public function borrar(Busqueda $busqueda): void
    {
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $busqueda->delete();

        session()->flash('status', 'El proceso fue eliminado.');
    }

    public function cambiarEstado(Busqueda $busqueda, string $estado): void
    {
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);
        abort_unless(array_key_exists($estado, Busqueda::ESTADOS), 422);

        $busqueda->update(['estado' => $estado]);
    }

    #[Title('Mis búsquedas · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.busquedas', [
            'busquedas' => Busqueda::query()
                ->where('empresa_id', auth()->user()->empresa?->id)
                ->withCount([
                    'candidatos',
                    'candidatos as favoritos_count' => fn ($query) => $query->where('favorito', true),
                ])
                ->latest()
                ->paginate(12),
            'estados' => Busqueda::ESTADOS,
        ]);
    }
}
