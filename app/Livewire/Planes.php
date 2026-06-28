<?php

namespace App\Livewire;

use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Planes extends Component
{
    #[Title('Planes · AD+50')]
    #[Layout('components.layouts.marketing')]
    public function render(): View
    {
        return view('livewire.planes', [
            'planes' => Plan::query()->where('audiencia', 'empresa')->orderBy('precio_clp')->get(),
            'planPostulante' => Plan::query()->where('audiencia', 'postulante')->first(),
        ]);
    }
}
