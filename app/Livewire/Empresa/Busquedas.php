<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
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

    /** Columna por la que se ordena el listado. */
    #[Url(history: true)]
    public string $orden = 'created_at';

    /** Sentido del orden: asc | desc. */
    #[Url(history: true)]
    public string $direccion = 'desc';

    /**
     * Columnas ordenables, mapeadas a la expresión SQL correspondiente. Los conteos
     * salen de los alias de withCount().
     *
     * @var array<string, string>
     */
    private const ORDENABLES = [
        'titulo' => 'titulo',
        'candidatos' => 'candidatos_count',
        'favoritos' => 'favoritos_count',
        'created_at' => 'created_at',
        'estado' => 'estado',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        if (! array_key_exists($this->orden, self::ORDENABLES)) {
            $this->orden = 'created_at';
        }

        if (! in_array($this->direccion, ['asc', 'desc'], true)) {
            $this->direccion = 'desc';
        }
    }

    /**
     * Ordena por una columna. Repetir la columna activa invierte el sentido; cambiar de
     * columna parte ascendente, salvo la fecha, donde lo natural es ver lo más reciente.
     */
    public function ordenarPor(string $campo): void
    {
        abort_unless(array_key_exists($campo, self::ORDENABLES), 404);

        if ($this->orden === $campo) {
            $this->direccion = $this->direccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->orden = $campo;
            $this->direccion = $campo === 'created_at' ? 'desc' : 'asc';
        }

        $this->resetPage();
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
                    'candidatos as favoritos_count' => fn ($query) => $query->confirmados()->where('favorito', true),
                ])
                ->orderBy(self::ORDENABLES[$this->orden], $this->direccion)
                // Desempate estable para que la paginación no baraje filas iguales.
                ->orderByDesc('id')
                ->paginate(12),
            'estados' => Busqueda::ESTADOS,
        ]);
    }
}
