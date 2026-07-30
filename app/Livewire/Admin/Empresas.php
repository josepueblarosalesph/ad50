<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Empresas extends Component
{
    /** Empresa cuyo formulario de plan está abierto. */
    public ?int $asignandoId = null;

    public string $asignandoRazonSocial = '';

    public ?int $planSeleccionado = null;

    /** Fecha hasta la que rige el plan (formato Y-m-d). */
    public string $vigenciaHasta = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
    }

    /**
     * Abre el formulario para asignar un plan a mano.
     *
     * Sirve para las empresas que pagaron por fuera de la pasarela (transferencia,
     * convenio) o para extender una vigencia: es lo mismo que hace el callback de
     * Flow, pero decidido por un administrador.
     */
    public function abrirAsignacion(int $empresaId): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $empresa = Empresa::query()->findOrFail($empresaId);

        $this->asignandoId = $empresa->id;
        $this->asignandoRazonSocial = $empresa->razon_social;
        $this->planSeleccionado = $empresa->plan_id;
        $this->vigenciaHasta = ($empresa->plan_hasta ?? now()->addYear())->format('Y-m-d');
        $this->resetErrorBag();

        $this->modal('asignar-plan')->show();
    }

    /** Propone la vigencia que corresponde al período del plan elegido. */
    public function updatedPlanSeleccionado(): void
    {
        $plan = Plan::query()->find($this->planSeleccionado);

        if ($plan === null) {
            return;
        }

        $empresa = Empresa::query()->find($this->asignandoId);

        // Igual que al pagar: si ya hay una vigencia futura, se extiende desde ahí.
        $this->vigenciaHasta = $plan->vigenciaDesde($empresa?->plan_hasta)->format('Y-m-d');
    }

    public function asignarPlan(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $validado = $this->validate([
            'planSeleccionado' => ['required', Rule::exists('planes', 'id')->where('audiencia', 'empresa')],
            'vigenciaHasta' => ['required', 'date', 'after:today'],
        ], attributes: [
            'planSeleccionado' => 'plan',
            'vigenciaHasta' => 'vigencia',
        ]);

        $empresa = Empresa::query()->findOrFail($this->asignandoId);

        $empresa->update([
            'plan_id' => $validado['planSeleccionado'],
            'plan_hasta' => $validado['vigenciaHasta'],
        ]);

        $this->reset('asignandoId', 'asignandoRazonSocial', 'planSeleccionado', 'vigenciaHasta');
        $this->modal('asignar-plan')->close();

        session()->flash('status', "Asignaste el plan {$empresa->fresh()->plan->nombre} a {$empresa->razon_social}.");
    }

    /** Deja a la empresa sin plan vigente. */
    public function quitarPlan(int $empresaId): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $empresa = Empresa::query()->findOrFail($empresaId);

        $empresa->update(['plan_id' => null, 'plan_hasta' => null]);

        session()->flash('status', "{$empresa->razon_social} quedó sin plan.");
    }

    public function activar(int $empresaId): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        $empresa = Empresa::query()->findOrFail($empresaId);
        abort_unless($empresa->estado_activacion === 'pendiente' && $empresa->datos_enviados_at !== null, 422);

        $empresa->update([
            'estado_activacion' => 'activa',
            'activada_at' => now(),
            'activada_por' => auth()->id(),
        ]);

        session()->flash('status', "La empresa {$empresa->razon_social} fue habilitada correctamente.");
    }

    #[Title('Empresas · Administración AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.admin.empresas', [
            'pendientes' => Empresa::query()->with('user', 'plan')->where('estado_activacion', 'pendiente')->latest('datos_enviados_at')->get(),
            'inactivas' => Empresa::query()->with('user', 'plan')->where('estado_activacion', 'inactiva')->latest()->get(),
            'activas' => Empresa::query()->with('user', 'activadaPor', 'plan')->where('estado_activacion', 'activa')->latest('activada_at')->get(),
            'planes' => Plan::query()->where('audiencia', 'empresa')->orderBy('precio_uf')->get(),
        ]);
    }
}
