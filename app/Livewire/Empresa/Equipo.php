<?php

namespace App\Livewire\Empresa;

use App\Concerns\OrdenaListado;
use App\Models\Empresa;
use App\Models\User;
use App\Rules\EmailCorporativo;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Equipo extends Component
{
    use OrdenaListado;

    public string $nombre = '';

    public string $apellidos = '';

    public string $email = '';

    public string $password = '';

    public function mount(): void
    {
        // Solo el contacto administrador gestiona el equipo.
        abort_unless(auth()->user()->esPrincipalEmpresa(), 403);

        $this->hidratarOrden();
    }

    public function agregar(): void
    {
        $empresa = auth()->user()->empresa;

        abort_unless(auth()->user()->esPrincipalEmpresa(), 403);

        if (! $empresa->puedeAgregarUsuario()) {
            $this->addError('email', 'Alcanzaste el máximo de '.Empresa::MAX_USUARIOS_ADICIONALES.' usuarios adicionales.');

            return;
        }

        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'apellidos' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', new EmailCorporativo],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => trim($validated['nombre'].' '.$validated['apellidos']),
            'nombres' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'empresa',
            'empresa_id' => $empresa->id,
            'acepta_ley_21719' => true,
        ]);

        // Igual que en el registro: la cuenta queda inactiva hasta que la persona abra
        // el enlace de verificación que le llega a su correo.
        event(new Registered($user));

        $this->reset('nombre', 'apellidos', 'email', 'password');

        session()->flash('status', 'Usuario agregado. Le enviamos un correo para verificar su cuenta; comparte las credenciales con la persona para que ingrese después de confirmarlo.');
    }

    public function eliminar(int $userId): void
    {
        $empresa = auth()->user()->empresa;

        abort_unless(auth()->user()->esPrincipalEmpresa(), 403);

        $user = User::query()
            ->where('empresa_id', $empresa->id)
            ->where('id', '!=', $empresa->user_id) // nunca el principal
            ->find($userId);

        $user?->delete();

        session()->flash('status', 'Usuario eliminado del equipo.');
    }

    /** @return array<string, string> */
    protected function columnasOrdenables(): array
    {
        return ['name' => 'name', 'email' => 'email'];
    }

    #[Title('Equipo · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $empresa = auth()->user()->empresa;

        return view('livewire.empresa.equipo', [
            'empresa' => $empresa,
            'principal' => $empresa->user,
            'adicionales' => $empresa->usuariosAdicionales()
                ->tap(fn ($query) => $this->aplicarOrden($query))
                ->get(),
            'disponibles' => $empresa->usuariosAdicionalesDisponibles(),
        ]);
    }
}
