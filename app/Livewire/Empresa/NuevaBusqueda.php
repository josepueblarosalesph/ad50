<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class NuevaBusqueda extends Component
{
    public string $titulo = '';

    public string $cargo = '';

    public string $industria = '';

    public int $aniosMinimos = 0;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'cargo' => ['nullable', 'string', 'max:160'],
            'industria' => ['nullable', 'string', 'max:120'],
            'aniosMinimos' => ['required', 'integer', 'min:0', 'max:80'],
        ]);

        $busqueda = Busqueda::query()->create([
            'empresa_id' => auth()->user()->empresa->id,
            'titulo' => $validated['titulo'],
            'rubro_oculto' => $validated['industria'] ?: null,
            'criterios' => [
                'cargo' => $validated['cargo'],
                'industria' => $validated['industria'],
                'min_anios' => $validated['aniosMinimos'],
            ],
            'estado' => 'activa',
        ]);

        $this->redirectRoute('empresa.resultados', ['busqueda' => $busqueda], navigate: true);
    }

    #[Title('Nueva búsqueda · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.nueva-busqueda');
    }
}
