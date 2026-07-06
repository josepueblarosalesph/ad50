<?php

namespace App\Livewire\Empresa;

use App\Rules\RutValido;
use App\Support\Rut;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Activacion extends Component
{
    public string $razonSocial = '';

    public string $rut = '';

    public string $rubro = '';

    public string $contactoPrincipalNombre = '';

    public string $contactoPrincipalCargo = '';

    public string $contactoPrincipalEmail = '';

    public string $contactoPrincipalTelefono = '';

    public string $contactoTecnicoNombre = '';

    public string $contactoTecnicoEmail = '';

    public string $contactoTecnicoTelefono = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        $empresa = auth()->user()->empresa;

        if ($empresa?->estaActiva()) {
            $this->redirectRoute('empresa.panel', navigate: true);

            return;
        }

        $this->razonSocial = $empresa?->razon_social ?? '';
        $this->rut = Rut::formatear($empresa?->rut ?? '');
        $this->rubro = $empresa?->rubro ?? '';
        $this->contactoPrincipalNombre = $empresa?->contacto_principal_nombre ?? auth()->user()->name;
        $this->contactoPrincipalCargo = $empresa?->contacto_principal_cargo ?? '';
        $this->contactoPrincipalEmail = $empresa?->contacto_principal_email ?? auth()->user()->email;
        $this->contactoPrincipalTelefono = $empresa?->contacto_principal_telefono ?? $empresa?->telefono ?? '';
        $this->contactoTecnicoNombre = $empresa?->contacto_tecnico_nombre ?? '';
        $this->contactoTecnicoEmail = $empresa?->contacto_tecnico_email ?? '';
        $this->contactoTecnicoTelefono = $empresa?->contacto_tecnico_telefono ?? '';
    }

    public function guardar(): void
    {
        $this->rut = Rut::formatear($this->rut);

        $validated = $this->validate([
            'razonSocial' => ['required', 'string', 'max:160'],
            'rut' => ['required', 'string', 'max:20', new RutValido],
            'rubro' => ['required', 'string', 'max:120'],
            'contactoPrincipalNombre' => ['required', 'string', 'max:160'],
            'contactoPrincipalCargo' => ['required', 'string', 'max:120'],
            'contactoPrincipalEmail' => ['required', 'email', 'max:255'],
            'contactoPrincipalTelefono' => ['required', 'string', 'max:30'],
            'contactoTecnicoNombre' => ['required', 'string', 'max:160'],
            'contactoTecnicoEmail' => ['required', 'email', 'max:255'],
            'contactoTecnicoTelefono' => ['required', 'string', 'max:30'],
        ]);

        auth()->user()->empresa()->update([
            'razon_social' => $validated['razonSocial'],
            'rut' => $validated['rut'],
            'rubro' => $validated['rubro'],
            'contacto_principal_nombre' => $validated['contactoPrincipalNombre'],
            'contacto_principal_cargo' => $validated['contactoPrincipalCargo'],
            'contacto_principal_email' => $validated['contactoPrincipalEmail'],
            'contacto_principal_telefono' => $validated['contactoPrincipalTelefono'],
            'contacto_tecnico_nombre' => $validated['contactoTecnicoNombre'],
            'contacto_tecnico_email' => $validated['contactoTecnicoEmail'],
            'contacto_tecnico_telefono' => $validated['contactoTecnicoTelefono'],
            'estado_activacion' => 'pendiente',
            'datos_enviados_at' => now(),
        ]);

        session()->flash('status', 'Tus antecedentes fueron enviados para revisión. Te avisaremos cuando la cuenta sea habilitada.');
    }

    #[Title('Activación de empresa · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.activacion', [
            'empresa' => auth()->user()->empresa->fresh(),
        ]);
    }
}
