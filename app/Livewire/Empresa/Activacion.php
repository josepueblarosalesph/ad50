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

    public string $contactoPrincipalDescripcion = '';

    public string $contactoTecnicoNombre = '';

    public string $contactoTecnicoCargo = '';

    public string $contactoTecnicoEmail = '';

    public string $contactoTecnicoTelefono = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        $empresa = auth()->user()->empresa;

        // Solo si el onboarding está completo (datos + plan pagado) se va al panel.
        // Ojo: no basta con planVigente, o se produce un bucle con el gate del panel.
        if ($empresa?->puedeOperar()) {
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
        $this->contactoPrincipalDescripcion = $empresa?->contacto_principal_descripcion ?? '';
        $this->contactoTecnicoNombre = $empresa?->contacto_tecnico_nombre ?? '';
        $this->contactoTecnicoCargo = $empresa?->contacto_tecnico_cargo ?? '';
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
            'contactoPrincipalDescripcion' => ['nullable', 'string', 'max:1000'],
            'contactoTecnicoNombre' => ['nullable', 'string', 'max:160'],
            'contactoTecnicoCargo' => ['nullable', 'string', 'max:120'],
            'contactoTecnicoEmail' => ['nullable', 'email', 'max:255'],
            'contactoTecnicoTelefono' => ['nullable', 'string', 'max:30'],
        ]);

        auth()->user()->empresa()->update([
            'razon_social' => $validated['razonSocial'],
            'rut' => $validated['rut'],
            'rubro' => $validated['rubro'],
            'contacto_principal_nombre' => $validated['contactoPrincipalNombre'],
            'contacto_principal_cargo' => $validated['contactoPrincipalCargo'],
            'contacto_principal_email' => $validated['contactoPrincipalEmail'],
            'contacto_principal_telefono' => $validated['contactoPrincipalTelefono'],
            'contacto_principal_descripcion' => $validated['contactoPrincipalDescripcion'],
            'contacto_tecnico_nombre' => $validated['contactoTecnicoNombre'],
            'contacto_tecnico_cargo' => $validated['contactoTecnicoCargo'],
            'contacto_tecnico_email' => $validated['contactoTecnicoEmail'],
            'contacto_tecnico_telefono' => $validated['contactoTecnicoTelefono'],
            'estado_activacion' => 'activa', // autoservicio: sin aprobación manual
            'datos_enviados_at' => now(),
        ]);

        // Si ya tenía un plan vigente, el onboarding queda completo → panel.
        // Si no, siguiente paso: elegir un plan y pagar.
        if (auth()->user()->empresa->fresh()->planVigente()) {
            $this->redirectRoute('empresa.panel', navigate: true);

            return;
        }

        session()->flash('status', 'Tus datos quedaron guardados. Elige un plan para activar tu cuenta.');
        $this->redirectRoute('empresa.planes', navigate: true);
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
