<?php

namespace App\Livewire\Admin;

use App\Concerns\OrdenaListado;
use App\Concerns\VerificaCuentas;
use App\Models\Busqueda;
use App\Models\Empresa;
use App\Models\Pago;
use App\Models\Postulante;
use App\Models\Publicacion;
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
 *
 * Y se editan (guardarDatos()) y se borran (eliminar()). Cuidado con el borrado: no es
 * solo una fila de `users`. `empresas.user_id` es `cascadeOnDelete`, así que eliminar al
 * contacto administrador de una empresa arrastra la empresa y, tras ella, sus búsquedas,
 * publicaciones, desbloqueos y pagos.
 *
 * El borrado no se bloquea por ello: el superadministrador puede llevarse una empresa y
 * su contabilidad por delante si eso es lo que quiere. Lo que la pantalla garantiza es
 * que no ocurra sin saberlo:
 *
 * 1. La confirmación enumera lo que se va a perder, con las cuentas reales, y destaca
 *    aparte los pagos ya cobrados, que son lo único irrecuperable de verdad.
 * 2. Hay que teclear ELIMINAR, como en el borrado de publicaciones.
 * 3. Si queda otro miembro del equipo, la empresa se le traspasa en vez de morir: que
 *    alguien deje la organización no es motivo para borrarla.
 * 4. Lo eliminado queda en el log —empresa, razón social y cuántos pagos confirmados se
 *    fueron con ella—, que pasa a ser el único rastro de que existió.
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

    // --- Edición de los datos de una cuenta ------------------------------------

    public ?int $editandoDatosId = null;

    public string $editNombres = '';

    public string $editApellidos = '';

    public string $editEmail = '';

    /** Vacío = se conserva la contraseña actual. */
    public string $editPassword = '';

    /** Correo con el que se abrió el formulario, para saber si cambió. */
    public string $editEmailOriginal = '';

    // --- Eliminación de una cuenta ---------------------------------------------

    public ?int $eliminandoId = null;

    public string $eliminandoNombre = '';

    public string $eliminandoEmail = '';

    /** Qué se borra junto con la cuenta, ya redactado para la confirmación. */
    /** @var list<string> */
    public array $eliminandoArrastra = [];

    /** Pagos ya cobrados que se perderían con el borrado; 0 si no se pierde ninguno. */
    public int $eliminandoPagosConfirmados = 0;

    /** A quién pasaría la empresa para salvarla, si procede. */
    public ?string $eliminandoTraspaso = null;

    /** Misma confirmación escrita que usa el borrado de publicaciones. */
    public string $confirmacionTexto = '';

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

    /** Abre el formulario con los datos básicos de una cuenta. */
    public function abrirEdicionDatos(int $userId): void
    {
        abort_unless(auth()->user()->esSuperadmin(), 403);

        $user = User::query()->findOrFail($userId);

        // La propia cuenta se edita en Mi cuenta, que además pide la contraseña actual
        // para cambiarla. Duplicarlo aquí solo abriría una vía más floja para lo mismo.
        abort_if($user->id === auth()->id(), 403);

        $this->editandoDatosId = $user->id;
        $this->editNombres = (string) ($user->nombres ?? $user->name);
        $this->editApellidos = (string) $user->apellidos;
        $this->editEmail = $user->email;
        $this->editEmailOriginal = $user->email;
        $this->editPassword = '';
        $this->resetErrorBag();

        $this->modal('editar-datos')->show();
    }

    /** Propone una contraseña fuerte para entregársela a la persona. */
    public function generarPasswordEdicion(): void
    {
        $this->editPassword = Str::password(14, symbols: false);
        $this->resetValidation('editPassword');
    }

    /**
     * Guarda nombre, correo y —solo si se escribió una— contraseña nueva.
     *
     * Dejar la contraseña en blanco conserva la actual: el caso corriente es corregir
     * una errata del correo, y obligar a teclear una contraseña para eso significaría
     * cambiársela a alguien sin necesidad.
     */
    public function guardarDatos(): void
    {
        abort_unless(auth()->user()->esSuperadmin(), 403);

        $user = User::query()->findOrFail($this->editandoDatosId);

        abort_if($user->id === auth()->id(), 403);

        $validado = $this->validate([
            'editNombres' => ['required', 'string', 'max:80'],
            'editApellidos' => ['nullable', 'string', 'max:80'],
            // La columna va explícita: la propiedad se llama `editEmail` y, sin decirlo,
            // Rule::unique la deduciría del nombre del campo y buscaría una columna
            // `editEmail` que no existe.
            'editEmail' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'editPassword' => ['nullable', 'string', 'min:8'],
        ], attributes: [
            'editNombres' => 'nombres',
            'editApellidos' => 'apellidos',
            'editEmail' => 'correo',
            'editPassword' => 'contraseña',
        ]);

        $cambioCorreo = mb_strtolower($validado['editEmail']) !== mb_strtolower($this->editEmailOriginal);
        $cambioPassword = $validado['editPassword'] !== '';

        $user->fill([
            'nombres' => $validado['editNombres'],
            'apellidos' => $validado['editApellidos'],
            'name' => trim($validado['editNombres'].' '.$validado['editApellidos']),
            'email' => $validado['editEmail'],
        ]);

        // Misma regla que en Mi cuenta: la dirección nueva no la ha demostrado nadie,
        // así que la cuenta vuelve a quedar sin verificar. Desde esta misma pantalla se
        // puede reenviar el enlace o darla por verificada a mano.
        if ($cambioCorreo) {
            $user->email_verified_at = null;
        }

        if ($cambioPassword) {
            $user->password = Hash::make($validado['editPassword']);
        }

        $user->save();

        // Cambiar la contraseña de otra persona deja a un tercero conociéndola: queda
        // registrado, igual que el alta manual y la verificación a mano.
        if ($cambioPassword) {
            logger()->info('Contraseña cambiada desde la administración', [
                'usuario' => $user->id,
                'administrador' => auth()->id(),
            ]);
        }

        $this->cerrarEdicionDatos();

        session()->flash('status', match (true) {
            $cambioCorreo && $cambioPassword => "Actualizamos los datos de {$user->name}. El correo cambió, así que la cuenta quedó sin verificar; entrégale también su contraseña nueva.",
            $cambioCorreo => "Actualizamos los datos de {$user->name}. Al cambiar el correo, la cuenta quedó sin verificar.",
            $cambioPassword => "Actualizamos los datos de {$user->name}. Entrégale su contraseña nueva.",
            default => "Actualizamos los datos de {$user->name}.",
        });
    }

    private function cerrarEdicionDatos(): void
    {
        $this->reset('editandoDatosId', 'editNombres', 'editApellidos', 'editEmail', 'editPassword', 'editEmailOriginal');
        $this->modal('editar-datos')->close();
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

    /**
     * Abre la confirmación de borrado, ya con el detalle de qué se lleva por delante.
     *
     * El resumen se calcula aquí y no en la vista porque el alcance real de un borrado
     * no se ve en la pantalla: `empresas.user_id` es `cascadeOnDelete`, así que eliminar
     * al contacto administrador de una empresa arrastra la empresa entera y, con ella,
     * sus búsquedas, publicaciones, desbloqueos y pagos. Una confirmación genérica de
     * «¿seguro?» no da la menor pista de eso.
     */
    public function abrirEliminar(int $userId): void
    {
        abort_unless(auth()->user()->esSuperadmin(), 403);

        $user = User::query()->findOrFail($userId);

        abort_if($user->id === auth()->id(), 403);

        $this->eliminandoId = $user->id;
        $this->eliminandoNombre = $user->name;
        $this->eliminandoEmail = $user->email;
        $this->eliminandoTraspaso = $this->herederoDeEmpresa($user)?->name;
        $this->eliminandoArrastra = $this->queArrastra($user);
        $this->eliminandoPagosConfirmados = $this->pagosConfirmadosQueSeVan($user);
        $this->confirmacionTexto = '';
        $this->resetErrorBag('confirmacionTexto');

        $this->modal('eliminar-usuario')->show();
    }

    public function eliminar(): void
    {
        abort_unless(auth()->user()->esSuperadmin(), 403);

        $user = User::query()->findOrFail($this->eliminandoId);

        // No hace falta comprobar que quede algún superadministrador: a esta pantalla
        // solo llega uno y no puede borrarse a sí mismo, así que siempre sobrevive él.
        abort_if($user->id === auth()->id(), 403);

        // Se teclea ELIMINAR, igual que al borrar una publicación. Aquí pesa más: esto
        // no tiene papelera ni deshacer.
        if (mb_strtoupper(trim($this->confirmacionTexto)) !== 'ELIMINAR') {
            $this->addError('confirmacionTexto', 'Escribe ELIMINAR para confirmar.');

            return;
        }

        $nombre = $user->name;
        $heredero = $this->herederoDeEmpresa($user);
        $empresa = $this->empresaQueAdministra($user);
        $pagosQueSeVan = $heredero === null ? $this->pagosConfirmadosQueSeVan($user) : 0;

        DB::transaction(function () use ($user, $heredero): void {
            // La empresa se salva pasándosela a otro miembro del equipo. Sin este
            // traspaso, el cascade se la llevaría por delante solo porque su contacto
            // administrador dejó la organización, que es justo lo que no se quiere.
            if ($heredero !== null) {
                Empresa::query()
                    ->where('user_id', $user->id)
                    ->update(['user_id' => $heredero->id]);
            }

            $user->delete();
        });

        // El log es lo único que queda de una empresa borrada: si se llevó pagos
        // confirmados por delante, ese número tiene que constar en alguna parte.
        logger()->info('Cuenta eliminada desde la administración', [
            'usuario' => $this->eliminandoId,
            'email' => $this->eliminandoEmail,
            'administrador' => auth()->id(),
            'empresa_traspasada_a' => $heredero?->id,
            'empresa_eliminada' => $heredero === null ? $empresa?->id : null,
            'razon_social_eliminada' => $heredero === null ? $empresa?->razon_social : null,
            'pagos_confirmados_eliminados' => $pagosQueSeVan,
        ]);

        $this->cerrarEliminar();
        $this->resetPage();

        session()->flash('status', $heredero === null
            ? "Eliminamos la cuenta de {$nombre}."
            : "Eliminamos la cuenta de {$nombre}. Su empresa quedó a cargo de {$heredero->name}.");
    }

    private function cerrarEliminar(): void
    {
        $this->reset('eliminandoId', 'eliminandoNombre', 'eliminandoEmail', 'eliminandoArrastra', 'eliminandoPagosConfirmados', 'eliminandoTraspaso', 'confirmacionTexto');
        $this->modal('eliminar-usuario')->close();
    }

    /**
     * Pagos ya cobrados que desaparecerían con este borrado.
     *
     * Ninguna cuenta se bloquea por esto: el superadministrador puede borrar aunque se
     * lleve por delante la contabilidad de una empresa. Lo que hace este número es que
     * nadie lo haga sin saberlo — se anuncia en la confirmación y queda en el log, que
     * es lo único que sobrevive al borrado.
     *
     * Devuelve 0 si no administra ninguna empresa o si hay heredero, porque entonces la
     * empresa (y sus pagos con ella) no se van a ninguna parte.
     */
    private function pagosConfirmadosQueSeVan(User $user): int
    {
        $empresa = $this->empresaQueAdministra($user);

        if ($empresa === null || $this->herederoDeEmpresa($user) !== null) {
            return 0;
        }

        return Pago::query()->where('empresa_id', $empresa->id)->where('estado', 'pagado')->count();
    }

    /** La empresa de la que este usuario es contacto administrador (dueño). */
    private function empresaQueAdministra(User $user): ?Empresa
    {
        return Empresa::query()->where('user_id', $user->id)->first();
    }

    /** Otro miembro del equipo que pueda quedarse con la empresa. */
    private function herederoDeEmpresa(User $user): ?User
    {
        $empresa = $this->empresaQueAdministra($user);

        if ($empresa === null) {
            return null;
        }

        return User::query()
            ->where('empresa_id', $empresa->id)
            ->whereKeyNot($user->id)
            ->orderBy('id')
            ->first();
    }

    /**
     * Lo que desaparece junto con la cuenta, en frases sueltas para la confirmación.
     *
     * @return list<string>
     */
    private function queArrastra(User $user): array
    {
        $arrastra = [];

        if ($user->postulante !== null) {
            $arrastra[] = 'Su ficha de postulante y las coincidencias que tenga con búsquedas de empresas.';
        }

        $empresa = $this->empresaQueAdministra($user);

        if ($empresa !== null && $this->herederoDeEmpresa($user) === null) {
            $busquedas = Busqueda::query()->where('empresa_id', $empresa->id)->count();
            $publicaciones = Publicacion::query()->where('empresa_id', $empresa->id)->count();
            $pagos = Pago::query()->where('empresa_id', $empresa->id)->count();

            $arrastra[] = "La empresa {$empresa->razon_social} completa: {$busquedas} ".
                ($busquedas === 1 ? 'búsqueda' : 'búsquedas').
                ", {$publicaciones} ".($publicaciones === 1 ? 'publicación' : 'publicaciones').
                " y {$pagos} ".($pagos === 1 ? 'pago' : 'pagos').'.';
        }

        $arrastra[] = 'Sus notas privadas sobre candidatos (las compartidas se conservan).';

        return $arrastra;
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
