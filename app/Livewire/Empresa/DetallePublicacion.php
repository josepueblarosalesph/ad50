<?php

namespace App\Livewire\Empresa;

use App\Models\Publicacion;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class DetallePublicacion extends Component
{
    public Publicacion $publicacion;

    public string $confirmacionTexto = '';

    public function mount(Publicacion $publicacion): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($publicacion->empresa_id === auth()->user()->empresa?->id, 403);

        $this->publicacion = $publicacion;
    }

    public function cambiarEstado(string $estado): void
    {
        abort_unless(array_key_exists($estado, Publicacion::ESTADOS), 422);

        $this->publicacion->update(['estado' => $estado]);
    }

    public function confirmarBorrado(): void
    {
        $this->confirmacionTexto = '';
        $this->resetErrorBag('confirmacionTexto');

        $this->modal('borrar-publicacion')->show();
    }

    public function borrar(): void
    {
        if (mb_strtoupper(trim($this->confirmacionTexto)) !== 'ELIMINAR') {
            $this->addError('confirmacionTexto', 'Escribe ELIMINAR para confirmar.');

            return;
        }

        // Borrado lógico: queda en papelera y puede deshacerse desde el listado.
        $this->publicacion->delete();

        session()->flash('publicacion-eliminada', [
            'id' => $this->publicacion->id,
            'cargo' => $this->publicacion->cargo,
        ]);

        $this->redirectRoute('empresa.publicaciones.index', navigate: true);
    }

    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.detalle-publicacion', [
            'totalPostulaciones' => $this->publicacion->postulaciones()->count(),
            'estados' => Publicacion::ESTADOS,
        ])->title($this->publicacion->cargo.' · AD+50');
    }
}
