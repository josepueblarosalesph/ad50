<?php

namespace App\Livewire\Empresa;

use App\Concerns\OrdenaListado;
use App\Models\Publicacion;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Panel extends Component
{
    use OrdenaListado;

    public function mount(): void
    {
        abort_unless(auth()->user()->esEmpresa(), 403);

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
            'cargo' => 'cargo',
            'postulaciones' => 'postulaciones_count',
            'vigente_hasta' => 'vigente_hasta',
            'estado' => 'estado',
        ];
    }

    /** @return list<string> */
    protected function columnasDescendentes(): array
    {
        return ['postulaciones', 'vigente_hasta'];
    }

    #[Title('Panel de empresa · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $empresa = auth()->user()->empresa;
        $publicaciones = Publicacion::query()
            ->withCount('postulaciones')
            ->where('empresa_id', $empresa?->id)
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.empresa.panel', [
            'empresa' => $empresa,
            // Se ordena la colección ya traída: hacerlo en la consulta cambiaría
            // *cuáles* son las 5 y dejarían de ser las recientes.
            'publicaciones' => $this->ordenarColeccion($publicaciones),
            'publicacionesVigentes' => Publicacion::query()
                ->where('empresa_id', $empresa?->id)
                ->vigentes()
                ->count(),
            // Cupos del plan: `disponibles`/`totales` en null son ilimitadas, y sin plan
            // no hay cupo que mostrar (ver `tienePlan`).
            'publicacionesDisponibles' => $empresa?->publicacionesDisponibles(),
            'publicacionesTotales' => $empresa?->publicacionesTotales(),
            'desbloqueosDisponibles' => $empresa?->desbloqueosDisponibles() ?? 0,
            'desbloqueosTotales' => $empresa?->desbloqueosTotales() ?? 0,
            'totalFavoritos' => $empresa?->favoritos()->count() ?? 0,
            'tienePlan' => $empresa?->plan !== null,
            'puedePublicar' => $empresa?->puedePublicar() ?? false,
        ]);
    }
}
