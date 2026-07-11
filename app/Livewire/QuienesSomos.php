<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class QuienesSomos extends Component
{
    #[Title('Quiénes somos · AD+50')]
    #[Layout('components.layouts.marketing')]
    public function render(): View
    {
        return view('livewire.quienes-somos');
    }
}
