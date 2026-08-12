<?php

namespace App\Livewire\Empresa;

use App\Concerns\AsociaCandidatosAPublicaciones;
use App\Models\Desbloqueo;
use App\Models\Empresa;
use App\Models\Favorito;
use App\Models\NotaCandidato;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\PublicacionCandidato;
use App\Services\MatchingService;
use App\Support\CandidatoDePublicacion;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Postulaciones extends Component
{
    use AsociaCandidatosAPublicaciones;
    use WithPagination;

    public Publicacion $publicacion;

    /**
     * Los dos filtros son ejes independientes y se combinan: de dónde viene la persona
     * y en qué etapa está. Antes iban mezclados en una sola fila de chips, donde
     * "Agregados" competía con los estados y no se podía, por ejemplo, ver los agregados
     * que ya están seleccionados.
     */

    /** Origen: `todos`, `postularon` o `agregados`. */
    #[Url(history: true)]
    public string $origen = 'todos';

    /** Etapa: `todos` o una clave de Postulacion::ESTADOS. */
    #[Url(history: true)]
    public string $estado = 'todos';

    /** @var array<string, string> */
    public const ORIGENES = [
        'todos' => 'Todos',
        'postularon' => 'Recibidos',
        'agregados' => 'Agregados',
    ];

    /**
     * Criterios de perfil provenientes del panel lateral (mismos de Prospección de Candidatos), aplicados al vuelo.
     *
     * @var array<string, mixed>|null
     */
    public ?array $criterios = null;

    /** Postulante cuyo detalle está abierto; null con el panel cerrado. */
    public ?int $detalleId = null;

    /** Candidato cuyas notas están abiertas en el panel rápido; null = cerrado. */
    public ?int $notasPostulanteId = null;

    /**
     * Las acciones sobre el candidato (favorito, notas, asociar) son de la cuenta, no de
     * esta publicación: se comportan igual que en Prospección de Candidatos.
     */
    public function toggleFavorito(int $postulanteId): void
    {
        abort_unless($this->candidatoAsociable($postulanteId), 404);

        auth()->user()->empresa->alternarFavorito($postulanteId);
    }

    /**
     * Postulantes que la empresa tiene guardados. Se consulta una vez por render y la
     * vista pregunta contra esta lista.
     *
     * @return list<int>
     */
    private function favoritosDeLaEmpresa(): array
    {
        return once(fn (): array => array_values(
            Favorito::query()
                ->where('empresa_id', $this->publicacion->empresa_id)
                ->pluck('postulante_id')
                ->map(fn ($id): int => (int) $id)
                ->all()
        ));
    }

    /** Abre la vista rápida de las notas del candidato; escribirlas sigue siendo cosa de su ficha. */
    public function abrirNotas(int $postulanteId): void
    {
        abort_unless($this->candidatoAsociable($postulanteId), 404);

        $this->notasPostulanteId = $postulanteId;
        $this->modal('notas-candidato')->show();
    }

    public function cerrarNotas(): void
    {
        $this->notasPostulanteId = null;
        $this->modal('notas-candidato')->close();
    }

    /**
     * Notas del candidato abierto que este usuario puede leer.
     *
     * @return Collection<int, NotaCandidato>
     */
    private function notasDelCandidato(): Collection
    {
        if ($this->notasPostulanteId === null) {
            return collect();
        }

        return NotaCandidato::query()
            ->where('empresa_id', $this->publicacion->empresa_id)
            ->where('postulante_id', $this->notasPostulanteId)
            ->visiblesPara(auth()->user())
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->get();
    }

    protected function empresaDeAsociacion(): ?Empresa
    {
        return auth()->user()->empresa;
    }

    /** Aquí no se asocia desde una búsqueda, sino desde el listado de la publicación. */
    protected function busquedaDeAsociacion(): ?int
    {
        return null;
    }

    protected function candidatoAsociable(int $postulanteId): bool
    {
        return auth()->user()->role === 'empresa'
            && auth()->user()->empresa?->id === $this->publicacion->empresa_id
            && $this->esCandidatoDeLaPublicacion($postulanteId);
    }

    /**
     * Abre el perfil completo sin salir del listado. La clave es el postulante y no la
     * postulación, porque la fila puede ser de alguien que la empresa agregó y que por
     * tanto no tiene postulación.
     */
    public function verDetalle(int $postulanteId): void
    {
        abort_unless($this->esCandidatoDeLaPublicacion($postulanteId), 404);

        $this->detalleId = $postulanteId;
        $this->modal('detalle-postulante')->show();
    }

    /** El postulante llegó a esta publicación por alguno de los dos caminos. */
    private function esCandidatoDeLaPublicacion(int $postulanteId): bool
    {
        return $this->publicacion->postulaciones()->where('postulante_id', $postulanteId)->exists()
            || $this->publicacion->candidatos()->whereKey($postulanteId)->exists();
    }

    public function cerrarDetalle(): void
    {
        $this->detalleId = null;
    }

    public function mount(Publicacion $publicacion): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($publicacion->empresa_id === auth()->user()->empresa?->id, 403);

        $this->publicacion = $publicacion;

        // Los dos filtros viajan en la URL: un valor de un enlace viejo o manipulado
        // equivale a no filtrar por ese eje.
        if (! array_key_exists($this->origen, self::ORIGENES)) {
            $this->origen = 'todos';
        }

        if ($this->estado !== 'todos' && ! array_key_exists($this->estado, Postulacion::ESTADOS)) {
            $this->estado = 'todos';
        }
    }

    /** @param  array<string, mixed>  $criterios */
    #[On('criterios-postulaciones')]
    public function filtrar(array $criterios): void
    {
        $this->criterios = $criterios;
        $this->resetPage(pageName: 'postulaciones');
    }

    /**
     * Los filtros llegan por wire:model desde dos desplegables, así que un valor
     * inventado se descarta en vez de reventar: se vuelve a "todos".
     */
    public function updated(string $campo): void
    {
        if ($campo === 'origen') {
            if (! array_key_exists($this->origen, self::ORIGENES)) {
                $this->origen = 'todos';
            }

            $this->resetPage(pageName: 'postulaciones');
        }

        if ($campo === 'estado') {
            if ($this->estado !== 'todos' && ! array_key_exists($this->estado, Postulacion::ESTADOS)) {
                $this->estado = 'todos';
            }

            $this->resetPage(pageName: 'postulaciones');
        }
    }

    /**
     * Mueve de etapa a un candidato de esta publicación, haya postulado o lo haya
     * agregado la empresa: el estado se guarda donde corresponda a cada caso.
     */
    public function cambiarEstado(int $postulanteId, string $estado): void
    {
        abort_unless(array_key_exists($estado, Postulacion::ESTADOS), 422);

        $candidato = $this->candidatos()
            ->first(fn (CandidatoDePublicacion $c): bool => $c->postulante->id === $postulanteId);

        $registro = $candidato?->registroDeEstado();

        abort_if($registro === null, 404);

        $registro->update(['estado' => $estado]);
    }

    /** Descarga del CV de un postulante que aplicó a esta publicación. No consume desbloqueos. */
    public function descargarCv(int $postulacionId): StreamedResponse
    {
        $postulacion = $this->publicacion->postulaciones()->with('postulante')->find($postulacionId);

        abort_if($postulacion === null || $postulacion->postulante === null, 404);

        $cvRuta = $postulacion->postulante->cv_ruta;

        abort_unless(filled($cvRuta) && Storage::disk('local')->exists($cvRuta), 404);

        return Storage::disk('local')->download(
            $cvRuta,
            'cv-postulante-'.$postulacion->postulante_id.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    #[Title('Postulantes · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $candidatos = $this->candidatos();

        $filtrados = $candidatos
            ->filter(fn (CandidatoDePublicacion $candidato): bool => $this->cumpleFiltroDeChip($candidato))
            ->filter(fn (CandidatoDePublicacion $candidato): bool => $this->cumpleCriterios($candidato->postulante))
            ->values();

        $total = $filtrados->count();
        $pagina = Paginator::resolveCurrentPage('postulaciones');

        $pagina = new LengthAwarePaginator(
            $filtrados->forPage($pagina, 15)->values(),
            $total,
            15,
            $pagina,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'postulaciones'],
        );

        return view('livewire.empresa.postulaciones', [
            'publicacion' => $this->publicacion,
            'candidatos' => $pagina,
            'totalCandidatos' => $candidatos->count(),
            'totalPostularon' => $candidatos->filter(fn (CandidatoDePublicacion $c): bool => $c->postulo())->count(),
            'totalAgregados' => $candidatos->filter(fn (CandidatoDePublicacion $c): bool => $c->agregado())->count(),
            'totalFiltradas' => $total,
            // Cuenta a todos, no solo a quienes postularon: los agregados también tienen
            // etapa, y si no aparecerían en el chip "En revisión" con un cero engañoso.
            'conteoPorEstado' => $candidatos
                ->countBy(fn (CandidatoDePublicacion $c): string => (string) $c->estado()),
            'estados' => Postulacion::ESTADOS,
            'origenes' => self::ORIGENES,
            'detalle' => $this->detalleId === null
                ? null
                : $candidatos->first(fn (CandidatoDePublicacion $c): bool => $c->postulante->id === $this->detalleId),
            'postulantesFavoritos' => $this->favoritosDeLaEmpresa(),
            'postulantesConNota' => NotaCandidato::query()
                ->where('empresa_id', $this->publicacion->empresa_id)
                ->whereIn('postulante_id', $pagina->pluck('postulante.id'))
                ->visiblesPara(auth()->user())
                ->pluck('postulante_id')
                ->all(),
            'notasDelCandidato' => $this->notasDelCandidato(),
            'publicacionesAsociables' => $this->publicacionesAsociables(),
            'publicacionesDelCandidato' => $this->publicacionesDelCandidato(),
            'asociacionesPorPostulante' => $this->conteoAsociaciones($pagina->pluck('postulante.id')),
        ]);
    }

    /**
     * Todas las personas de la publicación, vengan de una postulación o de haber sido
     * agregadas por la empresa. Se agrupan por postulante: quien fue agregado y además
     * postuló es una sola fila con los dos orígenes, no dos.
     *
     * @return Collection<int, CandidatoDePublicacion>
     */
    private function candidatos(): Collection
    {
        /** @var Collection<int, Postulacion> $postulaciones */
        $postulaciones = $this->publicacion->postulaciones()
            ->with('postulante.user')
            ->get()
            ->filter(fn (Postulacion $postulacion): bool => $postulacion->postulante !== null)
            ->keyBy('postulante_id');

        /** @var Collection<int, PublicacionCandidato> $asociaciones */
        $asociaciones = PublicacionCandidato::query()
            ->where('publicacion_id', $this->publicacion->id)
            ->with(['postulante.user', 'busqueda:id,titulo'])
            ->get()
            // Un candidato que ocultó su perfil deja de listarse, igual que en Prospección.
            ->filter(fn (PublicacionCandidato $asociacion): bool => $asociacion->postulante?->visible === true)
            ->keyBy('postulante_id');

        $ids = $postulaciones->keys()->merge($asociaciones->keys())->unique();

        // Perfiles que la empresa ya abrió: definen a quién se le muestra el nombre completo.
        $desbloqueados = Desbloqueo::query()
            ->where('empresa_id', $this->publicacion->empresa_id)
            ->whereIn('postulante_id', $ids)
            ->pluck('postulante_id')
            ->all();

        return $ids
            ->map(function (int $postulanteId) use ($postulaciones, $asociaciones, $desbloqueados): ?CandidatoDePublicacion {
                $postulacion = $postulaciones->get($postulanteId);
                $asociacion = $asociaciones->get($postulanteId);
                $postulante = $postulacion->postulante ?? $asociacion->postulante ?? null;

                return $postulante instanceof Postulante
                    ? new CandidatoDePublicacion(
                        $postulante,
                        $postulacion,
                        $asociacion,
                        in_array($postulanteId, $desbloqueados, true),
                    )
                    : null;
            })
            ->filter()
            ->sortByDesc(fn (CandidatoDePublicacion $candidato) => $candidato->fecha())
            ->values();
    }

    /** Los dos filtros se aplican a la vez: origen Y etapa. */
    private function cumpleFiltroDeChip(CandidatoDePublicacion $candidato): bool
    {
        $porOrigen = match ($this->origen) {
            'postularon' => $candidato->postulo(),
            'agregados' => $candidato->agregado(),
            default => true,
        };

        $porEstado = $this->estado === 'todos' || $candidato->estado() === $this->estado;

        return $porOrigen && $porEstado;
    }

    /** Evalúa el perfil del postulante contra los criterios del panel lateral (si hay). */
    private function cumpleCriterios(Postulante $postulante): bool
    {
        $criterios = $this->criterios ?? [];

        if ($criterios === []) {
            return true;
        }

        $detalle = app(MatchingService::class)->evaluar($postulante, $criterios);

        return ! collect($detalle)->contains(fn (array $criterio): bool => ! ($criterio['cumple'] ?? false));
    }
}
