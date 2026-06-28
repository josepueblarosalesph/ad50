<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Landing extends Component
{
    #[Title('AD+50 — El talento correcto, filtrado para cada búsqueda')]
    #[Layout('components.layouts.marketing')]
    public function render()
    {
        return view('livewire.landing');
    }
}
