<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Catálogo de planes tal como está configurado, en solo lectura.
 *
 * Los precios y cupos se definen en PlanSeeder y se despliegan con el código: esta
 * pantalla existe para *verificar* qué hay en producción y cuántas empresas están
 * suscritas a cada plan, no para editarlo. Asignar un plan a una empresa concreta
 * sigue estando en Administración › Empresas.
 */
class Planes extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);
    }

    /**
     * Empresas con cada plan hoy vigente, por plan_id.
     *
     * Se cuenta la vigencia y no solo `plan_id` porque una empresa conserva el plan
     * vencido hasta que renueva: contarla como suscrita inflaría el número.
     *
     * @return Collection<int, int>
     */
    private function empresasVigentesPorPlan(): Collection
    {
        return Empresa::query()->getQuery()
            ->whereNotNull('plan_id')
            ->whereDate('plan_hasta', '>=', now()->toDateString())
            ->selectRaw('plan_id, count(*) as total')
            ->groupBy('plan_id')
            ->pluck('total', 'plan_id');
    }

    #[Title('Planes · Administración AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $planes = Plan::query()->orderBy('audiencia')->orderBy('precio_uf')->get();

        return view('livewire.admin.planes', [
            'planesEmpresa' => $planes->where('audiencia', 'empresa'),
            'planesPostulante' => $planes->where('audiencia', 'postulante'),
            'empresasPorPlan' => $this->empresasVigentesPorPlan(),
        ]);
    }
}
