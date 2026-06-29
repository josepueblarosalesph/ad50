<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Landing extends Component
{
    #[Title('AD+50 — Experiencia lista para entrar en acción')]
    #[Layout('components.layouts.marketing')]
    public function render()
    {
        return view('livewire.landing');
    }
}
