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

    public ?int $borrandoId = null;

    public string $borrandoCargo = '';

    public string $confirmacionTexto = '';

    public ?int $eliminadoId = null;

    public string $eliminadoCargo = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        // El borrado hecho desde el detalle deja aquí el aviso con opción de deshacer.
        $eliminada = session('publicacion-eliminada');

        if (is_array($eliminada)) {
            $this->eliminadoId = (int) $eliminada['id'];
            $this->eliminadoCargo = (string) $eliminada['cargo'];
        }
    }

    public function cambiarEstado(Publicacion $publicacion, string $estado): void
    {
        abort_unless($publicacion->empresa_id === auth()->user()->empresa?->id, 403);
        abort_unless(array_key_exists($estado, Publicacion::ESTADOS), 422);

        $publicacion->update(['estado' => $estado]);
    }

    /** Abre el modal de confirmación de borrado para una publicación. */
    public function confirmarBorrado(Publicacion $publicacion): void
    {
        abort_unless($publicacion->empresa_id === auth()->user()->empresa?->id, 403);

        $this->borrandoId = $publicacion->id;
        $this->borrandoCargo = $publicacion->cargo;
        $this->confirmacionTexto = '';
        $this->resetErrorBag('confirmacionTexto');

        $this->modal('borrar-publicacion')->show();
    }

    public function borrar(): void
    {
        $publicacion = Publicacion::query()->find($this->borrandoId);

        abort_if($publicacion === null, 404);
        abort_unless($publicacion->empresa_id === auth()->user()->empresa?->id, 403);

        if (mb_strtoupper(trim($this->confirmacionTexto)) !== 'ELIMINAR') {
            $this->addError('confirmacionTexto', 'Escribe ELIMINAR para confirmar.');

            return;
        }

        // Borrado lógico: la publicación queda en papelera y puede deshacerse.
        $this->eliminadoId = $publicacion->id;
        $this->eliminadoCargo = $publicacion->cargo;
        $publicacion->delete();

        $this->reset('borrandoId', 'borrandoCargo', 'confirmacionTexto');
        $this->modal('borrar-publicacion')->close();
    }

    /** Restaura la última publicación eliminada (deshacer). */
    public function restaurar(): void
    {
        if ($this->eliminadoId === null) {
            return;
        }

        $publicacion = Publicacion::withTrashed()->find($this->eliminadoId);

        abort_if($publicacion === null, 404);
        abort_unless($publicacion->empresa_id === auth()->user()->empresa?->id, 403);

        $publicacion->restore();

        $this->reset('eliminadoId', 'eliminadoCargo');

        session()->flash('status', 'La publicación fue restaurada.');
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
            'estados' => Publicacion::ESTADOS,
        ]);
    }
}
