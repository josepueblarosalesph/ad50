<?php

namespace App\Livewire\Auth;

use App\Mail\SolicitudAccesoEquipo;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use App\Rules\EmailCorporativo;
use App\Rules\EmpresaYaRegistrada;
use App\Rules\RutValido;
use App\Support\Rut;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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

    /**
     * Empresa que ya tiene cuenta con el dominio del correo escrito. Mientras esté fijada,
     * el formulario ofrece enviarle la solicitud de acceso a su administrador en vez de
     * dejar a la persona en un callejón sin salida (ver Rules\EmpresaYaRegistrada).
     */
    public ?int $empresa_registrada_id = null;

    public string $empresa_registrada_nombre = '';

    public bool $solicitud_enviada = false;

    public function setRole(string $role): void
    {
        $this->role = in_array($role, ['postulante', 'empresa'], true) ? $role : 'postulante';
        $this->olvidarEmpresaRegistrada();
    }

    public function updatedEmail(): void
    {
        // El aviso pertenece al correo con el que se intentó registrar: si lo cambia, se cae.
        $this->olvidarEmpresaRegistrada();
    }

    public function updatedRut(): void
    {
        $this->rut = Rut::formatear($this->rut);
    }

    public function submit(): void
    {
        $this->rut = Rut::formatear($this->rut);

        $this->recordarEmpresaRegistrada();

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

        // TEMPORAL: se omite la verificación de correo. Los usuarios nuevos
        // quedan verificados automáticamente. Para reactivar la verificación,
        // elimina esta línea y restaura el redirect a verification.notice.
        $user->markEmailAsVerified();

        if ($this->role === 'postulante') {
            Postulante::create([
                'user_id' => $user->id,
                'completitud' => 10,
                'visible' => true,
                'onboarding_paso' => 1,
                'onboarding_completado' => false,
            ]);
        } else {
            // El contacto administrador queda enlazado a su empresa por el evento
            // `created` de Empresa (que fija users.empresa_id).
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

        // TEMPORAL: sin verificación de correo, se va directo al panel.
        $this->redirect(route('dashboard'), navigate: true);
    }

    /**
     * Avisa al administrador de la empresa ya registrada que esta persona quiere sumarse,
     * con los datos que necesita para crearle el usuario desde Equipo.
     */
    public function solicitarAcceso(): void
    {
        $empresa = $this->empresa_registrada_id === null
            ? null
            : Empresa::query()->with('user:id,email,name')->find($this->empresa_registrada_id);

        // Se revalida el dominio: los datos del formulario pudieron cambiar desde el aviso.
        if ($empresa === null || $this->empresaConDominioDelCorreo()?->id !== $empresa->id) {
            $this->olvidarEmpresaRegistrada();
            $this->addError('email', 'No pudimos identificar la cuenta de tu empresa. Revisa tu correo e inténtalo de nuevo.');

            return;
        }

        $this->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'apellidos' => ['required', 'string', 'max:80'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);

        $administrador = $empresa->user?->email;

        if ($administrador === null) {
            $this->addError('email', 'Esa cuenta no tiene un administrador con correo registrado. Escríbenos a contacto@adconsulting.cl y te ayudamos.');

            return;
        }

        // Una solicitud por correo cada 15 minutos: evita que el botón se use para spamear
        // la bandeja del administrador.
        $llave = 'solicitud-acceso-equipo:'.mb_strtolower($this->email);

        if (RateLimiter::tooManyAttempts($llave, 1)) {
            $this->solicitud_enviada = true;

            return;
        }

        RateLimiter::hit($llave, 900);

        Mail::to($administrador)->send(new SolicitudAccesoEquipo(
            empresa: $empresa,
            nombre: $this->nombre,
            apellidos: $this->apellidos,
            email: $this->email,
            telefono: $this->telefono !== '' ? $this->telefono : null,
        ));

        $this->solicitud_enviada = true;
    }

    /** Fija (o limpia) la empresa que ya tiene cuenta con el dominio del correo escrito. */
    protected function recordarEmpresaRegistrada(): void
    {
        $empresa = $this->empresaConDominioDelCorreo();

        if ($empresa === null) {
            $this->olvidarEmpresaRegistrada();

            return;
        }

        $this->empresa_registrada_id = $empresa->id;
        $this->empresa_registrada_nombre = (string) $empresa->razon_social;
    }

    protected function olvidarEmpresaRegistrada(): void
    {
        $this->empresa_registrada_id = null;
        $this->empresa_registrada_nombre = '';
        $this->solicitud_enviada = false;
    }

    /**
     * Empresa con cuenta para el dominio del correo escrito, solo cuando pedir acceso es
     * la salida correcta: registro de empresa y correo que todavía no es de nadie (si ya
     * existe el usuario, lo que corresponde es iniciar sesión).
     */
    protected function empresaConDominioDelCorreo(): ?Empresa
    {
        if ($this->role !== 'empresa' || $this->email === '') {
            return null;
        }

        if (User::query()->whereRaw('lower(email) = ?', [mb_strtolower(trim($this->email))])->exists()) {
            return null;
        }

        $dominio = EmpresaYaRegistrada::dominioDe($this->email);

        return $dominio === null ? null : EmpresaYaRegistrada::empresaConDominio($dominio);
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
            $rules['email'][] = new EmailCorporativo;
            $rules['email'][] = new EmpresaYaRegistrada;
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
