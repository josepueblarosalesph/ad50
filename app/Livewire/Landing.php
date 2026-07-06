<?php

namespace App\Livewire;

use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Landing extends Component
{
    #[Title('AD+50 — Experiencia lista para entrar en acción')]
    #[Layout('components.layouts.marketing')]
    public function render(): View
    {
        return view('livewire.landing', [
            'planes' => Plan::query()->where('audiencia', 'empresa')->orderBy('precio_clp')->get(),
            'planPostulante' => Plan::query()->where('audiencia', 'postulante')->first(),
        ]);
    }
}
