<?php

namespace App\Livewire\Admin;

use App\Concerns\OrdenaListado;
use App\Concerns\VerificaCuentas;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use App\Rules\RutValido;
use App\Support\Rut;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Todas las cuentas de la plataforma, con su rol, para el superadministrador.
 *
 * Es la única pantalla exclusiva del rol `superadmin`: el resto de la administración
 * la comparte con los admin. Cambiar el rol de alguien reescribe únicamente
 * `users.role`; la ficha de postulante o la empresa asociada se conservan intactas,
 * de modo que el cambio es reversible y no destruye información. Lo que falte lo
 * genera el onboarding del nuevo rol la próxima vez que la persona entre.
 *
 * Desde aquí también se crean cuentas a mano, con su contraseña y ya verificadas
 * (ver crearUsuario()): es la vía para dar de alta al equipo interno, montar cuentas
 * de demostración y resolver los registros que se atascan en el correo de verificación.
 */
class Usuarios extends Component
{
    use OrdenaListado;
    use VerificaCuentas;
    use WithPagination;

    #[Url(history: true)]
    public string $buscar = '';

    /** Rol por el que se filtra: todos | una clave de User::ROLES. */
    #[Url(history: true)]
    public string $rol = 'todos';

    /** Verificación del correo: todos | verificados | pendientes. */
    #[Url(history: true)]
    public string $verificacion = 'todos';

    /** Usuario cuyo formulario de rol está abierto. */
    public ?int $editandoId = null;

    public string $editandoNombre = '';

    public string $editandoEmail = '';

    /** Rol que tiene hoy el usuario que se está editando. */
    public string $rolActual = '';

    /** Rol elegido en el formulario. */
    public string $rolNuevo = '';

    // --- Alta manual de cuentas -------------------------------------------------

    public string $nuevoRol = 'postulante';

    public string $nuevoNombres = '';

    public string $nuevoApellidos = '';

    public string $nuevoEmail = '';

    public string $nuevoPassword = '';

    /** Empresa a la que se suma la cuenta; '' significa crear una empresa nueva. */
    public string $nuevaEmpresaId = '';

    public string $nuevaRazonSocial = '';

    public string $nuevoRut = '';

    public string $nuevoTelefono = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->esSuperadmin(), 403);

        if ($this->rol !== 'todos' && ! array_key_exists($this->rol, User::ROLES)) {
            $this->rol = 'todos';
        }

        if (! in_array($this->verificacion, ['todos', 'verificados', 'pendientes'], true)) {
            $this->verificacion = 'todos';
        }

        $this->hidratarOrden();
    }

    public function updated(string $campo): void
    {
        if (in_array($campo, ['buscar', 'rol', 'verificacion'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->rol = 'todos';
        $this->verificacion = 'todos';
        $this->resetPage();
    }

    /** Abre el formulario para cambiar el rol de una cuenta. */
    public function abrirCambioRol(int $userId): void
    {
        abort_unless(auth()->user()->esSuperadmin(), 403);

        $user = User::query()->findOrFail($userId);

        // Cambiarse el rol a uno mismo es la forma más rápida de perder el acceso: si
        // el único superadmin se degrada, ya no queda quién lo revierta desde la interfaz.
        abort_if($user->id === auth()->id(), 403);

        $this->editandoId = $user->id;
        $this->editandoNombre = $user->name;
        $this->editandoEmail = $user->email;
        $this->rolActual = $user->role;
        $this->rolNuevo = $user->role;
        $this->resetErrorBag();

        $this->modal('cambiar-rol')->show();
    }

    public function cambiarRol(): void
    {
        abort_unless(auth()->user()->esSuperadmin(), 403);

        $validado = $this->validate([
            'rolNuevo' => ['required', Rule::in(array_keys(User::ROLES))],
        ], attributes: [
            'rolNuevo' => 'rol',
        ]);

        $user = User::query()->findOrFail($this->editandoId);

        abort_if($user->id === auth()->id(), 403);

        if ($user->role === $validado['rolNuevo']) {
            $this->cerrarCambioRol();

            return;
        }

        $anterior = $user->rolLabel();

        // Solo el rol: la ficha de postulante y la empresa asociada quedan donde están.
        $user->update(['role' => $validado['rolNuevo']]);

        $this->cerrarCambioRol();

        session()->flash('status', "{$user->name} pasó de {$anterior} a {$user->fresh()->rolLabel()}.");
    }

    private function cerrarCambioRol(): void
    {
        $this->reset('editandoId', 'editandoNombre', 'editandoEmail', 'rolActual', 'rolNuevo');
        $this->modal('cambiar-rol')->close();
    }

    /** Abre el formulario de alta manual de una cuenta. */
    public function abrirCrearUsuario(): void
    {
        abort_unless(auth()->user()->esSuperadmin(), 403);

        $this->limpiarFormularioCreacion();
        $this->resetErrorBag();

        $this->modal('crear-usuario')->show();
    }

    public function updatedNuevoRut(): void
    {
        $this->nuevoRut = Rut::formatear($this->nuevoRut);
    }

    /** Rellena el campo con una contraseña fuerte para entregársela a la persona. */
    public function generarPassword(): void
    {
        $this->nuevoPassword = Str::password(14, symbols: false);
        $this->resetValidation('nuevoPassword');
    }

    /**
     * Crea una cuenta completa: usuario con su contraseña, la ficha o la empresa que le
     * corresponde según el rol, y el correo ya dado por verificado.
     *
     * Se salta a propósito el correo de verificación (no se emite `Registered`): la cuenta
     * no la pidió su titular, así que no hay nada que confirmar y mandarle un enlace solo
     * lo confundiría. A cambio se marca `email_verified_at` y se emite `Verified`, igual
     * que hace VerificaCuentas::marcarVerificada(), para que una cuenta creada aquí sea
     * indistinguible de una que sí pasó por el enlace.
     */
    public function crearUsuario(): void
    {
        abort_unless(auth()->user()->esSuperadmin(), 403);

        $this->nuevoRut = Rut::formatear($this->nuevoRut);

        $validado = $this->validate($this->reglasCreacion(), attributes: [
            'nuevoRol' => 'tipo de usuario',
            'nuevoNombres' => 'nombres',
            'nuevoApellidos' => 'apellidos',
            'nuevoEmail' => 'correo',
            'nuevoPassword' => 'contraseña',
            'nuevaEmpresaId' => 'empresa',
            'nuevaRazonSocial' => 'razón social',
            'nuevoRut' => 'RUT',
            'nuevoTelefono' => 'teléfono',
        ]);

        // Sumarse a una empresa existente consume uno de sus cupos de usuarios adicionales:
        // se comprueba antes de crear nada para no dejar un usuario huérfano si no cabe.
        $empresaExistente = null;

        if ($validado['nuevoRol'] === 'empresa' && $this->nuevaEmpresaId !== '') {
            $empresaExistente = Empresa::query()->findOrFail((int) $this->nuevaEmpresaId);

            if (! $empresaExistente->puedeAgregarUsuario()) {
                $this->addError('nuevaEmpresaId', 'Esa empresa ya tiene sus '.Empresa::MAX_USUARIOS_ADICIONALES.' usuarios adicionales.');

                return;
            }
        }

        $user = DB::transaction(function () use ($validado, $empresaExistente): User {
            $user = User::create([
                'name' => trim($validado['nuevoNombres'].' '.$validado['nuevoApellidos']),
                'nombres' => $validado['nuevoNombres'],
                'apellidos' => $validado['nuevoApellidos'],
                'email' => $validado['nuevoEmail'],
                'password' => Hash::make($validado['nuevoPassword']),
                'role' => $validado['nuevoRol'],
                'empresa_id' => $empresaExistente?->id,
                'acepta_ley_21719' => true,
            ]);

            if ($validado['nuevoRol'] === 'postulante') {
                // Mismo punto de partida que el registro: entra directo a completar su ficha.
                Postulante::create([
                    'user_id' => $user->id,
                    'completitud' => 10,
                    'visible' => true,
                    'onboarding_paso' => 1,
                    'onboarding_completado' => false,
                ]);
            }

            if ($validado['nuevoRol'] === 'empresa' && $empresaExistente === null) {
                // Los antecedentes los aporta el superadministrador, así que la empresa nace
                // con la activación resuelta: es lo mismo que deja Empresa/Activacion cuando
                // los envía la propia empresa. El plan sigue siendo aparte (Admin/Empresas).
                Empresa::create([
                    'user_id' => $user->id,
                    'razon_social' => $validado['nuevaRazonSocial'],
                    'rut' => $validado['nuevoRut'],
                    'telefono' => $validado['nuevoTelefono'],
                    'estado_activacion' => 'activa',
                    'datos_enviados_at' => now(),
                    'activada_at' => now(),
                    'activada_por' => auth()->id(),
                    'contacto_principal_nombre' => $user->name,
                    'contacto_principal_email' => $user->email,
                    'contacto_principal_telefono' => $validado['nuevoTelefono'],
                ]);
            }

            $user->markEmailAsVerified();

            return $user;
        });

        event(new Verified($user));

        // Una cuenta con contraseña conocida por quien no es su titular: queda quién la creó.
        logger()->info('Cuenta creada manualmente desde la administración', [
            'usuario_creado' => $user->id,
            'email' => $user->email,
            'rol' => $user->role,
            'administrador' => auth()->id(),
        ]);

        $this->cerrarCrearUsuario();
        $this->resetPage();

        session()->flash('status', "Creamos la cuenta de {$user->name} ({$user->rolLabel()}) con el correo ya verificado. Entrégale sus credenciales: puede entrar de inmediato.");
    }

    private function cerrarCrearUsuario(): void
    {
        $this->limpiarFormularioCreacion();
        $this->modal('crear-usuario')->close();
    }

    private function limpiarFormularioCreacion(): void
    {
        $this->reset(
            'nuevoRol', 'nuevoNombres', 'nuevoApellidos', 'nuevoEmail', 'nuevoPassword',
            'nuevaEmpresaId', 'nuevaRazonSocial', 'nuevoRut', 'nuevoTelefono',
        );
    }

    /** @return array<string, list<mixed>> */
    private function reglasCreacion(): array
    {
        $reglas = [
            'nuevoRol' => ['required', Rule::in(array_keys(User::ROLES))],
            'nuevoNombres' => ['required', 'string', 'max:80'],
            'nuevoApellidos' => ['required', 'string', 'max:80'],
            'nuevoEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nuevoPassword' => ['required', 'string', 'min:8'],
        ];

        if ($this->nuevoRol === 'empresa') {
            if ($this->nuevaEmpresaId !== '') {
                $reglas['nuevaEmpresaId'] = ['required', Rule::exists('empresas', 'id')];
            } else {
                // Empresa nueva: hacen falta los mismos antecedentes que pide el registro.
                $reglas['nuevaRazonSocial'] = ['required', 'string', 'max:160'];
                $reglas['nuevoRut'] = ['required', 'string', 'max:20', new RutValido];
                $reglas['nuevoTelefono'] = ['required', 'string', 'max:30'];
            }
        }

        return $reglas;
    }

    /** @return array<string, string> */
    protected function columnasOrdenables(): array
    {
        return [
            'created_at' => 'users.created_at',
            'name' => 'users.name',
            'email' => 'users.email',
            'role' => 'users.role',
        ];
    }

    /** @return list<string> */
    protected function columnasDescendentes(): array
    {
        return ['created_at'];
    }

    protected function ordenPorDefecto(): string
    {
        return 'created_at';
    }

    #[Title('Usuarios · Administración AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $query = User::query()
            ->when($this->buscar !== '', fn (Builder $q) => $q->where(
                fn (Builder $u) => $u->whereLike('name', '%'.$this->buscar.'%')->orWhereLike('email', '%'.$this->buscar.'%'),
            ))
            ->when($this->rol !== 'todos', fn (Builder $q) => $q->where('role', $this->rol))
            ->when($this->verificacion !== 'todos', fn (Builder $q) => $this->verificacion === 'verificados'
                ? $q->whereNotNull('email_verified_at')
                : $q->whereNull('email_verified_at'))
            ->tap(fn (Builder $q) => $this->aplicarOrden($q));

        return view('livewire.admin.usuarios', [
            'usuarios' => $query->paginate(20),
            'totalUsuarios' => User::query()->count(),
            'totalSinVerificar' => User::query()->whereNull('email_verified_at')->count(),
            // Conteo por rol para las pastillas de filtro, en una sola consulta.
            'conteoPorRol' => User::query()->getQuery()
                ->selectRaw('role, count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role'),
            'hayFiltros' => $this->buscar !== '' || $this->rol !== 'todos' || $this->verificacion !== 'todos',
            // Para el alta manual: a qué empresa ya registrada se puede sumar la cuenta nueva.
            'empresasDisponibles' => Empresa::query()->orderBy('razon_social')->get(['id', 'razon_social']),
        ]);
    }
}
