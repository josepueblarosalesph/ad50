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

    public ?int $borrandoId = null;

    public string $borrandoTitulo = '';

    public string $confirmacionTexto = '';

    public ?int $eliminadoId = null;

    public string $eliminadoTitulo = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
    }

    /** Abre el modal de confirmación de borrado para un proceso. */
    public function confirmarBorrado(Busqueda $busqueda): void
    {
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->borrandoId = $busqueda->id;
        $this->borrandoTitulo = $busqueda->titulo;
        $this->confirmacionTexto = '';
        $this->resetErrorBag('confirmacionTexto');

        $this->modal('borrar-proceso')->show();
    }

    public function borrar(): void
    {
        $busqueda = Busqueda::query()->find($this->borrandoId);

        abort_if($busqueda === null, 404);
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        if (mb_strtoupper(trim($this->confirmacionTexto)) !== 'ELIMINAR') {
            $this->addError('confirmacionTexto', 'Escribe ELIMINAR para confirmar.');

            return;
        }

        // Borrado lógico: el proceso queda en papelera y puede deshacerse.
        $this->eliminadoId = $busqueda->id;
        $this->eliminadoTitulo = $busqueda->titulo;
        $busqueda->delete();

        $this->reset('borrandoId', 'borrandoTitulo', 'confirmacionTexto');
        $this->modal('borrar-proceso')->close();
    }

    /** Restaura el último proceso eliminado (deshacer). */
    public function restaurar(): void
    {
        if ($this->eliminadoId === null) {
            return;
        }

        $busqueda = Busqueda::withTrashed()->find($this->eliminadoId);

        abort_if($busqueda === null, 404);
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $busqueda->restore();

        $this->reset('eliminadoId', 'eliminadoTitulo');

        session()->flash('status', 'El proceso fue restaurado.');
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
                    'candidatos' => fn ($query) => $query->confirmados(),
                    'candidatos as favoritos_count' => fn ($query) => $query->confirmados()->where('favorito', true),
                ])
                ->latest()
                ->paginate(12),
            'estados' => Busqueda::ESTADOS,
        ]);
    }
}
