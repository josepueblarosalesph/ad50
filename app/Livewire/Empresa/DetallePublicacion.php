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

    /** Quita a un candidato asociado desde Prospección de Candidatos (no toca sus postulaciones). */
    public function quitarCandidato(int $postulanteId): void
    {
        $this->publicacion->candidatos()->detach($postulanteId);
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
        $candidatosAsociados = $this->publicacion->candidatos()
            ->where('visible', true)
            ->with('user')
            ->orderBy('postulantes.id')
            ->get();

        return view('livewire.empresa.detalle-publicacion', [
            'totalPostulaciones' => $this->publicacion->postulaciones()->count(),
            'estados' => Publicacion::ESTADOS,
            'candidatosAsociados' => $candidatosAsociados,
            // Personas distintas en la publicación: quien postuló más quien fue agregado,
            // sin contar dos veces a quien llegó por los dos caminos.
            'totalCandidatos' => $this->publicacion->postulaciones()
                ->whereNotIn('postulante_id', $candidatosAsociados->modelKeys())
                ->count() + $candidatosAsociados->count(),
        ])->title($this->publicacion->cargo.' · AD+50');
    }
}
