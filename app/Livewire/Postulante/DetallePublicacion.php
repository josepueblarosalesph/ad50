<?php

namespace App\Livewire\Postulante;

use App\Concerns\PostulaAOfertas;
use App\Models\Publicacion;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Detalle de una oferta tal como la ve un postulante, con la opción de postular.
 * Solo se abren ofertas vigentes: una cerrada o vencida deja de ser pública.
 */
class DetallePublicacion extends Component
{
    use PostulaAOfertas;

    public Publicacion $publicacion;

    public function mount(Publicacion $publicacion): void
    {
        abort_unless(auth()->user()->role === 'postulante', 403);
        abort_unless($publicacion->estaVigente(), 404);

        $this->publicacion = $publicacion;
    }

    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $postulante = auth()->user()->postulante;

        return view('livewire.postulante.detalle-publicacion', [
            'yaPostulo' => $this->publicacion->postulaciones()->whereBelongsTo($postulante)->exists(),
            'publicacionSeleccionada' => $this->publicacionEnPostulacion(),
        ])->title($this->publicacion->cargo.' · AD+50');
    }
}
