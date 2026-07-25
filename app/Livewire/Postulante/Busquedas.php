<?php

namespace App\Livewire\Postulante;

use App\Models\Postulacion;
use App\Models\Publicacion;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Busquedas extends Component
{
    use WithPagination;

    public string $buscar = '';

    public string $modalidad = '';

    public string $comuna = '';

    public string $actividad = '';

    public ?int $postulandoId = null;

    /** @var array<int, string> */
    public array $respuestas = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'postulante', 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['buscar', 'modalidad', 'comuna', 'actividad'], true)) {
            $this->resetPage();
        }
    }

    public function abrirPostulacion(Publicacion $publicacion): void
    {
        abort_unless($publicacion->estado === 'publicada' && $publicacion->vigente_hasta->endOfDay()->isFuture(), 404);

        $postulante = auth()->user()->postulante;

        if ($publicacion->postulaciones()->whereBelongsTo($postulante)->exists()) {
            session()->flash('status', 'Ya postulaste a esta publicación.');

            return;
        }

        $this->postulandoId = $publicacion->id;
        $this->respuestas = array_fill(0, count($publicacion->preguntas ?? []), '');
        $this->resetErrorBag();
        $this->modal('postular-publicacion')->show();
    }

    public function postular(): void
    {
        $publicacion = Publicacion::query()->vigentes()->findOrFail($this->postulandoId);
        $postulante = auth()->user()->postulante;

        abort_if($postulante === null, 403);

        $reglas = ['respuestas' => ['array']];

        foreach ($publicacion->preguntas ?? [] as $index => $pregunta) {
            $reglas["respuestas.$index"] = ['required', 'string', 'max:1000'];
        }

        $validated = $this->validate($reglas);

        Postulacion::query()->firstOrCreate(
            [
                'publicacion_id' => $publicacion->id,
                'postulante_id' => $postulante->id,
            ],
            [
                'respuestas' => $validated['respuestas'],
                'estado' => 'enviada',
            ],
        );

        $this->reset('postulandoId', 'respuestas');
        $this->modal('postular-publicacion')->close();
        session()->flash('status', 'Tu postulación fue enviada correctamente.');
    }

    #[Title('Oportunidades laborales · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $postulante = auth()->user()->postulante;

        return view('livewire.postulante.busquedas', [
            'publicaciones' => Publicacion::query()
                ->vigentes()
                ->withExists([
                    'postulaciones as postulada' => fn (Builder $query) => $query->where('postulante_id', $postulante?->id),
                ])
                ->when($this->buscar !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query
                        ->whereLike('cargo', '%'.$this->buscar.'%')
                        ->orWhereLike('descripcion', '%'.$this->buscar.'%');
                }))
                ->when($this->modalidad !== '', fn (Builder $query) => $query->where('modalidad', $this->modalidad))
                ->when($this->comuna !== '', fn (Builder $query) => $query->whereLike('comuna', '%'.$this->comuna.'%'))
                ->when($this->actividad !== '', fn (Builder $query) => $query->where('actividad_empresa', $this->actividad))
                ->latest()
                ->paginate(12),
            'actividades' => CatalogosProfesionales::industrias(),
            'publicacionSeleccionada' => $this->postulandoId === null
                ? null
                : Publicacion::query()->find($this->postulandoId),
        ]);
    }
}
