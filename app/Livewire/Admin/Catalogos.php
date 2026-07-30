<?php

namespace App\Livewire\Admin;

use App\Models\TerminoCatalogo;
use App\Services\UsoDeTerminos;
use App\Support\CatalogosAdministrables;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Administración de los catálogos: industrias, cargos, regiones, habilidades, etc.
 *
 * Antes de editar o eliminar se comprueba si el término ya quedó guardado en fichas,
 * publicaciones o criterios de búsqueda. Si está en uso no se deja tocar: renombrarlo
 * dejaría esos registros apuntando a un valor inexistente.
 */
class Catalogos extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $catalogo = 'industria';

    #[Url(history: true)]
    public string $buscar = '';

    /** Término en edición; null cuando el formulario está cerrado. */
    public ?int $editandoId = null;

    public string $valor = '';

    /** Término que se está por eliminar, con el detalle de su uso. */
    public ?int $borrandoId = null;

    public string $borrandoValor = '';

    /** Motivo por el que no se puede editar o eliminar; vacío si sí se puede. */
    public string $bloqueo = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        if (! CatalogosAdministrables::existe($this->catalogo)) {
            $this->catalogo = 'industria';
        }
    }

    public function updated(string $campo): void
    {
        if (in_array($campo, ['catalogo', 'buscar'], true)) {
            $this->cerrarFormularios();
            $this->resetPage();
        }
    }

    public function cerrarFormularios(): void
    {
        $this->reset('editandoId', 'valor', 'borrandoId', 'borrandoValor', 'bloqueo');
        $this->resetErrorBag();
    }

    /** Abre el formulario para agregar un término nuevo. */
    public function abrirNuevo(): void
    {
        $this->cerrarFormularios();
        $this->editandoId = 0;
        $this->modal('termino')->show();
    }

    /**
     * Abre la edición. Si el término está en uso no se permite: solo se muestra dónde.
     */
    public function abrirEdicion(int $id, UsoDeTerminos $uso): void
    {
        $termino = $this->terminoDelCatalogo($id);

        $this->cerrarFormularios();
        $this->editandoId = $termino->id;
        $this->valor = $termino->valor;
        $this->bloqueo = $this->motivoDeBloqueo($uso->detalle($this->catalogo, $termino->valor), 'editar');

        $this->modal('termino')->show();
    }

    public function guardar(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $validado = $this->validate([
            'valor' => [
                'required', 'string', 'max:190',
                Rule::unique('terminos_catalogo', 'valor')
                    ->where('catalogo', $this->catalogo)
                    ->ignore($this->editandoId ?: null),
            ],
        ], attributes: ['valor' => 'término']);

        if ($this->editandoId) {
            $termino = $this->terminoDelCatalogo($this->editandoId);

            // Se revalida al guardar: el uso pudo aparecer mientras el formulario estaba abierto.
            abort_if(app(UsoDeTerminos::class)->estaEnUso($this->catalogo, $termino->valor), 422);

            $termino->update(['valor' => trim($validado['valor'])]);
            $mensaje = 'Actualizamos el término.';
        } else {
            TerminoCatalogo::query()->create([
                'catalogo' => $this->catalogo,
                'valor' => trim($validado['valor']),
                // Los términos nuevos van al final del catálogo.
                'orden' => (int) TerminoCatalogo::query()->where('catalogo', $this->catalogo)->max('orden') + 1,
            ]);
            $mensaje = 'Agregamos el término al catálogo.';
        }

        $this->cerrarFormularios();
        $this->modal('termino')->close();
        session()->flash('status', $mensaje);
    }

    /**
     * Abre la confirmación de borrado. Si está en uso muestra el impedimento; si no,
     * pide confirmar igualmente antes de eliminar.
     */
    public function confirmarBorrado(int $id, UsoDeTerminos $uso): void
    {
        $termino = $this->terminoDelCatalogo($id);

        $this->cerrarFormularios();
        $this->borrandoId = $termino->id;
        $this->borrandoValor = $termino->valor;
        $this->bloqueo = $this->motivoDeBloqueo($uso->detalle($this->catalogo, $termino->valor), 'eliminar');

        $this->modal('borrar-termino')->show();
    }

    public function borrar(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $termino = $this->terminoDelCatalogo($this->borrandoId ?? 0);

        abort_if(app(UsoDeTerminos::class)->estaEnUso($this->catalogo, $termino->valor), 422);

        $termino->delete();

        $this->cerrarFormularios();
        $this->modal('borrar-termino')->close();
        session()->flash('status', 'Eliminamos el término del catálogo.');
    }

    /**
     * Mensaje de impedimento cuando el término ya está en uso, con el detalle de dónde.
     *
     * @param  array{total: int, detalle: array<string, int>}  $uso
     */
    private function motivoDeBloqueo(array $uso, string $accion): string
    {
        if ($uso['total'] === 0) {
            return '';
        }

        $donde = collect($uso['detalle'])
            ->map(fn (int $cantidad, string $lugar): string => "{$cantidad} en {$lugar}")
            ->implode('; ');

        return "No se puede {$accion} porque está siendo usado ({$donde}).";
    }

    private function terminoDelCatalogo(int $id): TerminoCatalogo
    {
        return TerminoCatalogo::query()
            ->where('catalogo', $this->catalogo)
            ->findOrFail($id);
    }

    #[Title('Catálogos · Administración AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $definicion = CatalogosAdministrables::definicion($this->catalogo);

        return view('livewire.admin.catalogos', [
            'catalogos' => collect(CatalogosAdministrables::todos())->map(fn (array $d): string => $d['etiqueta']),
            'definicion' => $definicion,
            'terminos' => TerminoCatalogo::query()
                ->where('catalogo', $this->catalogo)
                ->when($this->buscar !== '', fn ($q) => $q->whereLike('valor', '%'.$this->buscar.'%'))
                ->orderBy('orden')
                ->orderBy('valor')
                ->paginate(25),
            'totalTerminos' => TerminoCatalogo::query()->where('catalogo', $this->catalogo)->count(),
        ]);
    }
}
