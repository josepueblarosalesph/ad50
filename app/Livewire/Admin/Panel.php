<?php

namespace App\Livewire\Admin;

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
    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);
    }

    #[Title('Administración · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.admin.panel', [
            'empresas' => Empresa::query()->with('user', 'plan')->latest()->take(5)->get(),
            'totalEmpresas' => Empresa::query()->where('estado_activacion', 'activa')->count(),
            'empresasPendientes' => Empresa::query()->where('estado_activacion', 'pendiente')->count(),
            'totalPostulantes' => Postulante::query()->count(),
            'totalBusquedas' => Busqueda::query()->where('estado', 'activa')->count(),
            'totalCoincidencias' => BusquedaCandidato::query()->count(),
        ]);
    }
}
