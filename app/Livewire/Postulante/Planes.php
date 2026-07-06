<?php

namespace App\Livewire\Postulante;

use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Planes extends Component
{
    #[Title('Plan para postulantes · AD+50')]
    #[Layout('components.layouts.marketing')]
    public function render(): View
    {
        return view('livewire.postulante.planes', [
            'plan' => Plan::query()->where('audiencia', 'postulante')->first(),
        ]);
    }
}
