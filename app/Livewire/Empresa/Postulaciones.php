<?php

namespace App\Livewire\Empresa;

use App\Models\Postulacion;
use App\Models\Publicacion;
use App\Services\MatchingService;
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

    /** Filtro por estado de la postulación (todas | enviada | en_revision | seleccionada | descartada). */
    #[Url(history: true)]
    public string $estado = 'todas';

    /**
     * Criterios de perfil provenientes del panel lateral (mismos de Prospección de Candidatos), aplicados al vuelo.
     *
     * @var array<string, mixed>|null
     */
    public ?array $criterios = null;

    /** Postulación cuyo detalle está abierto; null con el panel cerrado. */
    public ?int $detalleId = null;

    /** Abre el perfil completo del postulante sin salir del listado. */
    public function verDetalle(int $postulacionId): void
    {
        abort_unless($this->publicacion->postulaciones()->whereKey($postulacionId)->exists(), 404);

        $this->detalleId = $postulacionId;
        $this->modal('detalle-postulante')->show();
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
        abort_unless($estado === 'todas' || array_key_exists($estado, Postulacion::ESTADOS), 404);

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

    #[Title('Postulaciones · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $base = $this->publicacion->postulaciones()->with('postulante.user');

        // Conteos por estado (sin filtro de perfil) para los chips.
        $conteoPorEstado = (clone $base)->selectRaw('estado, count(*) as total')
            ->groupBy('estado')->pluck('total', 'estado');

        /** @var Collection<int, Postulacion> $postulaciones */
        $postulaciones = (clone $base)
            ->when($this->estado !== 'todas', fn ($query) => $query->where('estado', $this->estado))
            ->latest()
            ->get()
            ->filter(fn (Postulacion $postulacion): bool => $this->cumpleCriterios($postulacion));

        $total = $postulaciones->count();
        $pagina = Paginator::resolveCurrentPage('postulaciones');

        $pagina = new LengthAwarePaginator(
            $postulaciones->forPage($pagina, 15)->values(),
            $total,
            15,
            $pagina,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'postulaciones'],
        );

        return view('livewire.empresa.postulaciones', [
            'publicacion' => $this->publicacion,
            'postulaciones' => $pagina,
            'totalPostulaciones' => (int) $conteoPorEstado->sum(),
            'totalFiltradas' => $total,
            'conteoPorEstado' => $conteoPorEstado,
            'estados' => Postulacion::ESTADOS,
            'detalle' => $this->detalleId === null
                ? null
                : $this->publicacion->postulaciones()
                    ->with('postulante.user')
                    ->find($this->detalleId),
        ]);
    }

    /** Evalúa el perfil del postulante contra los criterios del panel lateral (si hay). */
    private function cumpleCriterios(Postulacion $postulacion): bool
    {
        if ($postulacion->postulante === null) {
            return false;
        }

        $criterios = $this->criterios ?? [];

        if ($criterios === []) {
            return true;
        }

        $detalle = app(MatchingService::class)->evaluar($postulacion->postulante, $criterios);

        return ! collect($detalle)->contains(fn (array $criterio): bool => ! ($criterio['cumple'] ?? false));
    }
}
