<?php

namespace App\Livewire\Auth;

use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use App\Rules\RutValido;
use App\Support\Rut;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

class Register extends Component
{
    #[Url(as: 'tipo')]
    public string $role = 'postulante';

    public string $nombre = '';

    public string $apellidos = '';

    public string $email = '';

    public string $password = '';

    public string $razon_social = '';

    public string $rut = '';

    public string $telefono = '';

    public bool $acepta = true;

    public function setRole(string $role): void
    {
        $this->role = in_array($role, ['postulante', 'empresa'], true) ? $role : 'postulante';
    }

    public function updatedRut(): void
    {
        $this->rut = Rut::formatear($this->rut);
    }

    public function submit(): void
    {
        $this->rut = Rut::formatear($this->rut);

        $this->validate(messages: [
            'acepta.accepted' => 'Debes autorizar el tratamiento de datos.',
        ]);

        $user = User::create([
            'name' => trim($this->nombre.' '.$this->apellidos),
            'nombres' => $this->nombre,
            'apellidos' => $this->apellidos,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $this->role,
            'acepta_ley_21719' => true,
        ]);

        if ($this->role === 'postulante') {
            Postulante::create([
                'user_id' => $user->id,
                'completitud' => 10,
                'visible' => true,
                'onboarding_paso' => 1,
                'onboarding_completado' => false,
            ]);
        } else {
            Empresa::create([
                'user_id' => $user->id,
                'razon_social' => $this->razon_social,
                'rut' => $this->rut,
                'telefono' => $this->telefono,
                'estado_activacion' => 'inactiva',
                'contacto_principal_nombre' => $user->name,
                'contacto_principal_email' => $user->email,
                'contacto_principal_telefono' => $this->telefono,
            ]);
        }

        event(new Registered($user));
        Auth::login($user, remember: true);

        $this->redirect(route('verification.notice'), navigate: true);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        $rules = [
            'role' => ['required', 'in:postulante,empresa'],
            'nombre' => ['required', 'string', 'max:80'],
            'apellidos' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'acepta' => ['accepted'],
        ];

        if ($this->role === 'empresa') {
            $rules['razon_social'] = ['required', 'string', 'max:160'];
            $rules['rut'] = ['required', 'string', 'max:20', new RutValido];
            $rules['telefono'] = ['required', 'string', 'max:30'];
        }

        return $rules;
    }

    #[Title('Crear cuenta · AD+50')]
    #[Layout('components.layouts.marketing')]
    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
