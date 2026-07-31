<?php

namespace App\Livewire\Empresa;

use App\Models\Desbloqueo;
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
    use WithPagination;

    public Publicacion $publicacion;

    /** Chip activo: `todas`, un estado de postulación, o `agregados` (los que sumó la empresa). */
    #[Url(history: true)]
    public string $estado = 'todas';

    /** Valor del chip que filtra por origen en vez de por estado de postulación. */
    private const FILTRO_AGREGADOS = 'agregados';

    /**
     * Criterios de perfil provenientes del panel lateral (mismos de Prospección de Candidatos), aplicados al vuelo.
     *
     * @var array<string, mixed>|null
     */
    public ?array $criterios = null;

    /** Postulante cuyo detalle está abierto; null con el panel cerrado. */
    public ?int $detalleId = null;

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
    }

    /** @param  array<string, mixed>  $criterios */
    #[On('criterios-postulaciones')]
    public function filtrar(array $criterios): void
    {
        $this->criterios = $criterios;
        $this->resetPage(pageName: 'postulaciones');
    }

    public function mostrarEstado(string $estado): void
    {
        abort_unless(
            in_array($estado, ['todas', self::FILTRO_AGREGADOS], true) || array_key_exists($estado, Postulacion::ESTADOS),
            404
        );

        $this->estado = $estado;
        $this->resetPage(pageName: 'postulaciones');
    }

    public function cambiarEstado(int $postulacionId, string $estado): void
    {
        abort_unless(array_key_exists($estado, Postulacion::ESTADOS), 422);

        $postulacion = $this->publicacion->postulaciones()->find($postulacionId);

        abort_if($postulacion === null, 404);

        $postulacion->update(['estado' => $estado]);
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
            'conteoPorEstado' => $candidatos
                ->filter(fn (CandidatoDePublicacion $c): bool => $c->postulo())
                ->countBy(fn (CandidatoDePublicacion $c): string => (string) $c->estado()),
            'estados' => Postulacion::ESTADOS,
            'filtroAgregados' => self::FILTRO_AGREGADOS,
            'detalle' => $this->detalleId === null
                ? null
                : $candidatos->first(fn (CandidatoDePublicacion $c): bool => $c->postulante->id === $this->detalleId),
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

    /** Chip activo: `todas`, un estado de postulación o los agregados por la empresa. */
    private function cumpleFiltroDeChip(CandidatoDePublicacion $candidato): bool
    {
        return match ($this->estado) {
            'todas' => true,
            self::FILTRO_AGREGADOS => $candidato->agregado(),
            default => $candidato->estado() === $this->estado,
        };
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
