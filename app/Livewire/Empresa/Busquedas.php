<?php

namespace App\Livewire\Empresa;

use App\Concerns\OrdenaListado;
use App\Models\Busqueda;
use App\Models\Favorito;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Busquedas extends Component
{
    use OrdenaListado;
    use WithPagination;

    public ?int $borrandoId = null;

    public string $borrandoTitulo = '';

    public string $confirmacionTexto = '';

    public ?int $eliminadoId = null;

    public string $eliminadoTitulo = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        $this->hidratarOrden();
    }

    /** @return array<string, string> */
    protected function columnasOrdenables(): array
    {
        return [
            'created_at' => 'created_at',
            'titulo' => 'titulo',
            'candidatos' => 'candidatos_count',
            'favoritos' => 'favoritos_count',
            'estado' => 'estado',
        ];
    }

    /** @return list<string> */
    protected function columnasDescendentes(): array
    {
        return ['created_at', 'candidatos', 'favoritos'];
    }

    /** Abre el modal de confirmación de borrado para una búsqueda. */
    public function confirmarBorrado(Busqueda $busqueda): void
    {
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->borrandoId = $busqueda->id;
        $this->borrandoTitulo = $busqueda->titulo;
        $this->confirmacionTexto = '';
        $this->resetErrorBag('confirmacionTexto');

        $this->modal('borrar-busqueda')->show();
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

        // Borrado lógico: la búsqueda queda en papelera y puede deshacerse.
        $this->eliminadoId = $busqueda->id;
        $this->eliminadoTitulo = $busqueda->titulo;
        $busqueda->delete();

        $this->reset('borrandoId', 'borrandoTitulo', 'confirmacionTexto');
        $this->modal('borrar-busqueda')->close();
    }

    /** Restaura la última búsqueda eliminada (deshacer). */
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

        session()->flash('status', 'La búsqueda fue restaurada.');
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
                    // Candidatos de esta búsqueda que además están guardados en la cuenta.
                    'candidatos as favoritos_count' => fn ($query) => $query->confirmados()->whereIn(
                        'postulante_id',
                        Favorito::query()->where('empresa_id', auth()->user()->empresa?->id)->select('postulante_id')
                    ),
                ])
                ->tap(fn ($query) => $this->aplicarOrden($query))
                ->paginate(12),
            'estados' => Busqueda::ESTADOS,
        ]);
    }
}
