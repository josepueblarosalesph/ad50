<?php

namespace App\Livewire\Admin;

use App\Concerns\OrdenaListado;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Postulante;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Panel extends Component
{
    use OrdenaListado;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

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
        return [
            'razon_social' => 'razon_social',
            'plan' => 'plan.nombre',
            'estado' => 'estado_activacion',
        ];
    }

    #[Title('Administración · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.admin.panel', [
            // Igual que en el panel de empresa: se ordena lo ya traído para que sigan
            // siendo las 5 más recientes.
            'empresas' => $this->ordenarColeccion(
                Empresa::query()->with('user', 'plan')->latest()->take(5)->get()
            ),
            'totalEmpresas' => Empresa::query()->where('estado_activacion', 'activa')->count(),
            'empresasPendientes' => Empresa::query()->where('estado_activacion', 'pendiente')->count(),
            'totalPostulantes' => Postulante::query()->count(),
            'totalBusquedas' => Busqueda::query()->whereIn('estado', Busqueda::ESTADOS_ACTIVOS)->count(),
            'totalCoincidencias' => BusquedaCandidato::query()->confirmados()->whereHas('busqueda')->count(),
        ]);
    }
}
