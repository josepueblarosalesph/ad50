<?php

namespace App\Livewire\Admin;

use App\Concerns\OrdenaListado;
use App\Concerns\VerificaCuentas;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
        ]);
    }
}
