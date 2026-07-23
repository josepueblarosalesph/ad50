<?php

namespace App\Livewire\Empresa;

use App\Models\Empresa;
use App\Models\User;
use App\Rules\EmailCorporativo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Equipo extends Component
{
    public string $nombre = '';

    public string $apellidos = '';

    public string $email = '';

    public string $password = '';

    public function mount(): void
    {
        // Solo el contacto principal gestiona el equipo.
        abort_unless(auth()->user()->esPrincipalEmpresa(), 403);
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

        // TEMPORAL: verificación de correo desactivada (ver Register).
        $user->markEmailAsVerified();

        $this->reset('nombre', 'apellidos', 'email', 'password');

        session()->flash('status', 'Usuario agregado. Comparte las credenciales con la persona para que ingrese.');
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

    #[Title('Equipo · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $empresa = auth()->user()->empresa;

        return view('livewire.empresa.equipo', [
            'empresa' => $empresa,
            'principal' => $empresa->user,
            'adicionales' => $empresa->usuariosAdicionales()->orderBy('name')->get(),
            'disponibles' => $empresa->usuariosAdicionalesDisponibles(),
        ]);
    }
}
