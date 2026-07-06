<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Empresas extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
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
            'pendientes' => Empresa::query()->with('user')->where('estado_activacion', 'pendiente')->latest('datos_enviados_at')->get(),
            'inactivas' => Empresa::query()->with('user')->where('estado_activacion', 'inactiva')->latest()->get(),
            'activas' => Empresa::query()->with('user', 'activadaPor')->where('estado_activacion', 'activa')->latest('activada_at')->get(),
        ]);
    }
}
