<?php

namespace App\Livewire\Admin;

use App\Models\MensajeContacto;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Bandeja de los mensajes que llegan desde la pantalla de Ayuda.
 *
 * Es la fuente de verdad del contacto: aquí no se pierde nada aunque falle un correo.
 * Abrir un mensaje lo marca como leído, y marcarlo como respondido lo saca de la lista
 * de pendientes; el estado es de la administración entera, no de cada admin.
 */
class Mensajes extends Component
{
    use WithPagination;

    /** Filtro de estado: 'pendientes' (nuevo + leído), 'todos' o un estado concreto. */
    #[Url(history: true)]
    public string $estado = 'pendientes';

    /** Filtro de motivo: 'todos' o una de las claves de MensajeContacto::MOTIVOS. */
    #[Url(history: true)]
    public string $motivo = 'todos';

    /** Mensaje abierto en el panel de lectura; null = cerrado. */
    public ?int $abiertoId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        if (! in_array($this->estado, ['pendientes', 'todos', ...array_keys(MensajeContacto::ESTADOS)], true)) {
            $this->estado = 'pendientes';
        }

        if ($this->motivo !== 'todos' && ! array_key_exists($this->motivo, MensajeContacto::MOTIVOS)) {
            $this->motivo = 'todos';
        }
    }

    public function updated(string $campo): void
    {
        if (in_array($campo, ['estado', 'motivo'], true)) {
            $this->resetPage(pageName: 'mensajes');
        }
    }

    /** Abre el mensaje y, si estaba sin leer, lo marca como leído. */
    public function abrir(int $mensajeId): void
    {
        $mensaje = $this->mensaje($mensajeId);

        if ($mensaje->estado === 'nuevo') {
            $mensaje->update(['estado' => 'leido']);
        }

        $this->abiertoId = $mensaje->id;
        $this->modal('mensaje-contacto')->show();
    }

    public function cerrar(): void
    {
        $this->abiertoId = null;
        $this->modal('mensaje-contacto')->close();
    }

    public function marcarRespondido(int $mensajeId): void
    {
        $this->mensaje($mensajeId)->update([
            'estado' => 'respondido',
            'respondido_at' => now(),
        ]);

        $this->cerrar();
    }

    /** Devuelve un mensaje pendiente al estado leído, por si se cerró por error. */
    public function reabrir(int $mensajeId): void
    {
        $this->mensaje($mensajeId)->update([
            'estado' => 'leido',
            'respondido_at' => null,
        ]);
    }

    private function mensaje(int $mensajeId): MensajeContacto
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        return MensajeContacto::query()->findOrFail($mensajeId);
    }

    #[Title('Mensajes · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $consulta = MensajeContacto::query()
            ->with('user:id,role')
            ->when($this->motivo !== 'todos', fn (Builder $q) => $q->where('motivo', $this->motivo))
            ->when($this->estado === 'pendientes', fn (Builder $q) => $q->pendientes())
            ->when(
                ! in_array($this->estado, ['pendientes', 'todos'], true),
                fn (Builder $q) => $q->where('estado', $this->estado),
            );

        return view('livewire.admin.mensajes', [
            'mensajes' => $consulta->latest()->paginate(15, pageName: 'mensajes'),
            'abierto' => $this->abiertoId === null
                ? null
                : MensajeContacto::query()->with('user:id,role')->find($this->abiertoId),
            'motivos' => MensajeContacto::MOTIVOS,
            'estados' => MensajeContacto::ESTADOS,
            'totalPendientes' => MensajeContacto::query()->pendientes()->count(),
            // Conteo por motivo, solo de lo pendiente: es lo que orienta por dónde empezar.
            'pendientesPorMotivo' => MensajeContacto::query()
                ->pendientes()
                ->selectRaw('motivo, count(*) as total')
                ->groupBy('motivo')
                ->pluck('total', 'motivo')
                ->all(),
        ]);
    }
}
