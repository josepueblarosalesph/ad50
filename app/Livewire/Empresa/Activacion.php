<?php

namespace App\Livewire\Empresa;

use App\Models\User;
use App\Rules\EmailCorporativo;
use App\Rules\RutValido;
use App\Support\Rut;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    /**
     * @var array<int, array{nombre: string, apellidos: string, email: string, password: string}>
     */
    public array $usuarios = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->esEmpresa(), 403);

        $empresa = auth()->user()->empresa;

        if (! ($empresa?->planVigente() ?? false)) {
            $this->redirectRoute('empresa.planes', navigate: true);

            return;
        }

        // Solo si el onboarding está completo (datos + plan pagado) se va al panel.
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
        $this->usuarios = array_fill(0, 3, [
            'nombre' => '',
            'apellidos' => '',
            'email' => '',
            'password' => '',
        ]);
    }

    /**
     * Vacía la contraseña de los usuarios opcionales que no tienen ningún otro dato.
     *
     * Los gestores de contraseñas del navegador rellenan solos el campo «contraseña
     * temporal» de estas fichas —basta con que la persona acepte el autocompletado en
     * cualquier campo de más arriba— aunque la empresa no quiera crear ningún usuario.
     * Esa contraseña fantasma disparaba el `required_with` de nombre, apellidos y correo,
     * y dejaba el onboarding bloqueado sin que se entendiera por qué. Una ficha con solo
     * la contraseña no describe a nadie, así que se descarta antes de validar.
     */
    private function descartarUsuariosSoloConContrasena(): void
    {
        foreach ($this->usuarios as $indice => $usuario) {
            $identificado = trim($usuario['nombre']) !== ''
                || trim($usuario['apellidos']) !== ''
                || trim($usuario['email']) !== '';

            if (! $identificado) {
                $this->usuarios[$indice]['password'] = '';
            }
        }
    }

    /**
     * Los mensajes por defecto de `required_with` enumeran las llaves crudas del arreglo
     * («usuarios.0.apellidos / usuarios.0.email …»), ilegibles para quien llena el
     * formulario. Se reemplazan por una instrucción que además recuerda la salida: dejar
     * la ficha entera en blanco si no se quiere crear ese usuario.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'usuarios.*.nombre.required_with' => 'Escribe los nombres de este usuario, o deja su ficha completamente en blanco si no quieres crearlo.',
            'usuarios.*.apellidos.required_with' => 'Escribe los apellidos de este usuario, o deja su ficha completamente en blanco si no quieres crearlo.',
            'usuarios.*.email.required_with' => 'Escribe el correo de este usuario, o deja su ficha completamente en blanco si no quieres crearlo.',
            'usuarios.*.password.required_with' => 'Define una contraseña temporal para este usuario, o deja su ficha completamente en blanco si no quieres crearlo.',
        ];
    }

    public function guardar(): void
    {
        if (! (auth()->user()->empresa?->planVigente() ?? false)) {
            $this->redirectRoute('empresa.planes', navigate: true);

            return;
        }

        $this->rut = Rut::formatear($this->rut);
        $this->descartarUsuariosSoloConContrasena();

        $validated = $this->validate([
            'razonSocial' => ['required', 'string', 'max:160'],
            'rut' => ['required', 'string', 'max:20', new RutValido],
            'rubro' => ['required', 'string', 'max:120'],
            'contactoPrincipalNombre' => ['required', 'string', 'max:160'],
            'contactoPrincipalCargo' => ['required', 'string', 'max:120'],
            'contactoPrincipalEmail' => ['required', 'email', 'max:255'],
            'contactoPrincipalTelefono' => ['required', 'string', 'max:30'],
            'contactoPrincipalDescripcion' => ['nullable', 'string', 'max:1000'],
            'usuarios' => ['array', 'max:3'],
            'usuarios.*.nombre' => ['nullable', 'required_with:usuarios.*.apellidos,usuarios.*.email,usuarios.*.password', 'string', 'max:80'],
            'usuarios.*.apellidos' => ['nullable', 'required_with:usuarios.*.nombre,usuarios.*.email,usuarios.*.password', 'string', 'max:80'],
            'usuarios.*.email' => ['nullable', 'required_with:usuarios.*.nombre,usuarios.*.apellidos,usuarios.*.password', 'email', 'max:255', 'distinct', 'unique:users,email', new EmailCorporativo],
            'usuarios.*.password' => ['nullable', 'required_with:usuarios.*.nombre,usuarios.*.apellidos,usuarios.*.email', 'string', 'min:8'],
        ]);

        /** @var list<User> $usuariosCreados */
        $usuariosCreados = [];

        DB::transaction(function () use ($validated, &$usuariosCreados): void {
            $empresa = auth()->user()->empresa;

            $empresa->update([
                'razon_social' => $validated['razonSocial'],
                'rut' => $validated['rut'],
                'rubro' => $validated['rubro'],
                'contacto_principal_nombre' => $validated['contactoPrincipalNombre'],
                'contacto_principal_cargo' => $validated['contactoPrincipalCargo'],
                'contacto_principal_email' => $validated['contactoPrincipalEmail'],
                'contacto_principal_telefono' => $validated['contactoPrincipalTelefono'],
                'contacto_principal_descripcion' => $validated['contactoPrincipalDescripcion'],
                'estado_activacion' => 'activa',
                'datos_enviados_at' => now(),
            ]);

            foreach ($validated['usuarios'] as $usuario) {
                if ($usuario['email'] === '') {
                    continue;
                }

                $nuevoUsuario = User::create([
                    'name' => trim($usuario['nombre'].' '.$usuario['apellidos']),
                    'nombres' => $usuario['nombre'],
                    'apellidos' => $usuario['apellidos'],
                    'email' => $usuario['email'],
                    'password' => Hash::make($usuario['password']),
                    'role' => 'empresa',
                    'empresa_id' => $empresa->id,
                    'acepta_ley_21719' => true,
                ]);

                $usuariosCreados[] = $nuevoUsuario;
            }
        });

        // Fuera de la transacción: el correo de verificación se envía solo si los
        // usuarios quedaron efectivamente guardados. Sin abrir ese enlace, sus cuentas
        // no pueden entrar (middleware `verified`).
        foreach ($usuariosCreados as $nuevoUsuario) {
            event(new Registered($nuevoUsuario));
        }

        session()->flash('status', 'Tus datos quedaron guardados. Tu cuenta ya está activa.');
        $this->redirectRoute('empresa.panel', navigate: true);
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
