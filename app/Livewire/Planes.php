<?php

namespace App\Livewire;

use App\Models\Plan;
use App\Support\Funcionalidades;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Planes extends Component
{
    /**
     * Con el cobro apagado la página queda oculta y se devuelve al inicio: los planes no
     * se anuncian mientras la plataforma sea gratis para las empresas. La vista se
     * conserva intacta y vuelve con AD50_COBRO_EMPRESAS=true.
     */
    public function mount(): void
    {
        if (! Funcionalidades::cobroAEmpresas()) {
            $this->redirectRoute('home');
        }
    }

    #[Title('Planes · AD+50')]
    #[Layout('components.layouts.marketing')]
    public function render(): View
    {
        return view('livewire.planes', [
            'planes' => Plan::query()->contratables()->where('audiencia', 'empresa')->orderBy('precio_uf')->get(),
        ]);
    }
}
