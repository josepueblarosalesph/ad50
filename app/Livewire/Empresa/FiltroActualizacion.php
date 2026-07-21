<?php

namespace App\Livewire\Empresa;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Control del menú de filtros que fija la antigüedad de la última actualización de
 * la ficha. No es un criterio de matching: solo acota el listado ya calzado, así que
 * vive en un componente aparte y le avisa a Resultados por evento (mismo patrón que
 * FiltrosBusqueda). Se monta dos veces (escritorio y móvil); el evento mantiene ambas
 * instancias en sintonía.
 */
class FiltroActualizacion extends Component
{
    public const OPCIONES = ['todas', 'mes', '1a3', '3a6', 'mas6'];

    public string $actualizacion = 'todas';

    public function mount(string $actual = 'todas'): void
    {
        $this->actualizacion = $this->normalizar($actual);
    }

    public function updatedActualizacion(): void
    {
        $this->actualizacion = $this->normalizar($this->actualizacion);
        $this->dispatch('actualizacion-cambiada', valor: $this->actualizacion);
    }

    /**
     * Sincroniza la instancia gemela (Livewire no reenvía el evento a quien lo emitió).
     */
    #[On('actualizacion-cambiada')]
    public function sincronizar(string $valor): void
    {
        $this->actualizacion = $this->normalizar($valor);
    }

    private function normalizar(string $valor): string
    {
        return in_array($valor, self::OPCIONES, true) ? $valor : 'todas';
    }

    public function render(): View
    {
        return view('livewire.empresa.filtro-actualizacion');
    }
}
