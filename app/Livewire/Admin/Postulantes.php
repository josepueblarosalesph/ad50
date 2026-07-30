<?php

namespace App\Livewire\Admin;

use App\Concerns\OrdenaListado;
use App\Models\Postulante;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de todos los postulantes de la plataforma para el administrador.
 */
class Postulantes extends Component
{
    use OrdenaListado;
    use WithPagination;

    #[Url(history: true)]
    public string $buscar = '';

    /** Visibilidad: todos | visibles | ocultos. */
    #[Url(history: true)]
    public string $visibilidad = 'todos';

    /** Onboarding: todos | completo | incompleto. */
    #[Url(history: true)]
    public string $onboarding = 'todos';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        if (! in_array($this->visibilidad, ['todos', 'visibles', 'ocultos'], true)) {
            $this->visibilidad = 'todos';
        }

        if (! in_array($this->onboarding, ['todos', 'completo', 'incompleto'], true)) {
            $this->onboarding = 'todos';
        }

        $this->hidratarOrden();
    }

    public function updated(string $campo): void
    {
        if (in_array($campo, ['buscar', 'visibilidad', 'onboarding'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->visibilidad = 'todos';
        $this->onboarding = 'todos';
        $this->resetPage();
    }

    /** @return array<string, string> */
    protected function columnasOrdenables(): array
    {
        return [
            'created_at' => 'postulantes.created_at',
            'cargo_actual' => 'postulantes.cargo_actual',
            'completitud' => 'postulantes.completitud',
            'anios_experiencia' => 'postulantes.anios_experiencia',
            'actualizacion' => 'postulantes.updated_at',
        ];
    }

    /** @return list<string> */
    protected function columnasDescendentes(): array
    {
        return ['created_at', 'completitud', 'anios_experiencia', 'actualizacion'];
    }

    #[Title('Postulantes · Administración AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $query = Postulante::query()
            ->with('user')
            ->when($this->buscar !== '', fn (Builder $q) => $q->whereHas(
                'user',
                fn (Builder $u) => $u->whereLike('name', '%'.$this->buscar.'%')->orWhereLike('email', '%'.$this->buscar.'%'),
            ))
            ->when($this->visibilidad !== 'todos', fn (Builder $q) => $q->where('visible', $this->visibilidad === 'visibles'))
            ->when($this->onboarding !== 'todos', fn (Builder $q) => $q->where('onboarding_completado', $this->onboarding === 'completo'))
            ->tap(fn (Builder $q) => $this->aplicarOrden($q));

        return view('livewire.admin.postulantes', [
            'postulantes' => $query->paginate(20),
            'totalPostulantes' => Postulante::query()->count(),
            'totalVisibles' => Postulante::query()->where('visible', true)->count(),
            'hayFiltros' => $this->buscar !== '' || $this->visibilidad !== 'todos' || $this->onboarding !== 'todos',
        ]);
    }
}
