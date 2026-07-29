<?php

namespace App\Livewire\Empresa;

use App\Concerns\OrdenaListado;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Panel extends Component
{
    use OrdenaListado;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        $this->hidratarOrden();
    }

    /** Sin orden elegido, se respeta el `latest()` de la consulta. */
    protected function ordenPorDefecto(): string
    {
        return '';
    }

    /** @return array<string, string> */
    protected function columnasOrdenables(): array
    {
        return ['titulo' => 'titulo', 'candidatos' => 'candidatos_count', 'estado' => 'estado'];
    }

    /** @return list<string> */
    protected function columnasDescendentes(): array
    {
        return ['candidatos'];
    }

    #[Title('Panel de empresa · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $empresa = auth()->user()->empresa;
        $busquedas = Busqueda::query()
            ->withCount(['candidatos' => fn ($query) => $query->confirmados()])
            ->where('empresa_id', $empresa?->id)
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.empresa.panel', [
            'empresa' => $empresa,
            // Se ordena la colección ya traída: hacerlo en la consulta cambiaría
            // *cuáles* son las 5 y dejarían de ser las recientes.
            'busquedas' => $this->ordenarColeccion($busquedas),
            'totalCandidatos' => BusquedaCandidato::query()
                ->confirmados()
                ->whereHas('busqueda', fn ($query) => $query->where('empresa_id', $empresa?->id))
                ->count(),
            'puedePublicar' => $empresa?->puedePublicar() ?? false,
        ]);
    }
}
