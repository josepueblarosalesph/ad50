<?php

namespace App\Livewire\Empresa;

use App\Concerns\AsociaCandidatosAPublicaciones;
use App\Models\BusquedaCandidato;
use App\Models\Desbloqueo;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\Publicacion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Todos los candidatos que la empresa marcó como favoritos, en una sola lista.
 *
 * El favorito vive en `busqueda_candidato`, así que una misma persona marcada en dos
 * búsquedas produce dos filas. Aquí se agrupa por candidato: cada persona aparece una
 * vez y las búsquedas donde está marcada se muestran como chips.
 */
class Favoritos extends Component
{
    use AsociaCandidatosAPublicaciones;
    use WithPagination;

    /** Búsqueda de origen: 'todas' o el id de una búsqueda de la empresa. */
    #[Url(history: true)]
    public string $busqueda = 'todas';

    /** Publicación asociada: 'todas', 'sin' (sin asociar) o el id de una publicación. */
    #[Url(history: true)]
    public string $publicacion = 'todas';

    /** Estado de desbloqueo: 'todos', 'desbloqueados' o 'bloqueados'. */
    #[Url(history: true)]
    public string $desbloqueo = 'todos';

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        if ($this->busqueda !== 'todas' && ! $this->busquedasDeLaEmpresa()->has((int) $this->busqueda)) {
            $this->busqueda = 'todas';
        }

        // Se valida contra todas las publicaciones (incluidas las cerradas): filtrar por
        // una cerrada es legítimo, aunque no se pueda asociar candidatos nuevos a ella.
        if ($this->publicacion !== 'todas' && $this->publicacion !== 'sin'
            && ! $this->publicacionesDeLaEmpresa()->has((int) $this->publicacion)) {
            $this->publicacion = 'todas';
        }

        if (! in_array($this->desbloqueo, ['todos', 'desbloqueados', 'bloqueados'], true)) {
            $this->desbloqueo = 'todos';
        }
    }

    public function updated(string $campo): void
    {
        if (in_array($campo, ['busqueda', 'publicacion', 'desbloqueo'], true)) {
            $this->resetPage(pageName: 'favoritos');
        }
    }

    public function limpiarFiltros(): void
    {
        $this->busqueda = 'todas';
        $this->publicacion = 'todas';
        $this->desbloqueo = 'todos';
        $this->resetPage(pageName: 'favoritos');
    }

    /** Quita el favorito de un candidato en una búsqueda concreta. */
    public function quitarFavorito(int $busquedaId, int $postulanteId): void
    {
        $match = BusquedaCandidato::query()
            ->where('busqueda_id', $busquedaId)
            ->where('postulante_id', $postulanteId)
            ->whereHas('busqueda', fn (Builder $query) => $query->where('empresa_id', $this->empresaId()))
            ->first();

        abort_if($match === null, 404);

        $match->update(['favorito' => false]);
        $this->resetPage(pageName: 'favoritos');
    }

    protected function empresaDeAsociacion(): ?Empresa
    {
        return auth()->user()->empresa;
    }

    /** Si hay un filtro de búsqueda activo se usa como origen; si no, queda sin trazabilidad. */
    protected function busquedaDeAsociacion(): ?int
    {
        return $this->busqueda === 'todas' ? null : (int) $this->busqueda;
    }

    /** Solo se asocian candidatos que sean favoritos vigentes de esta empresa. */
    protected function candidatoAsociable(int $postulanteId): bool
    {
        return auth()->user()->role === 'empresa'
            && $this->empresaId() !== null
            && $this->favoritosDeLaEmpresa()->where('postulante_id', $postulanteId)->exists();
    }

    private function empresaId(): ?int
    {
        return auth()->user()->empresa?->id;
    }

    /**
     * Filas de favoritos confirmados de la empresa, sobre postulantes visibles.
     *
     * @return Builder<BusquedaCandidato>
     */
    private function favoritosDeLaEmpresa(): Builder
    {
        return BusquedaCandidato::query()
            ->confirmados()
            ->where('favorito', true)
            ->whereHas('busqueda', fn (Builder $query) => $query->where('empresa_id', $this->empresaId()))
            ->whereHas('postulante', fn (Builder $query) => $query->where('visible', true));
    }

    /**
     * Búsquedas de la empresa, indexadas por id, para el desplegable del filtro.
     *
     * @return Collection<int, string>
     */
    private function busquedasDeLaEmpresa(): Collection
    {
        $empresa = auth()->user()->empresa;

        if ($empresa === null) {
            return collect();
        }

        return once(fn (): Collection => $empresa->busquedas()
            ->orderBy('titulo')
            ->pluck('titulo', 'id'));
    }

    /**
     * Publicaciones de la empresa para el filtro, indexadas por id.
     * Incluye las cerradas: siguen siendo un filtro válido para revisar el historial.
     *
     * @return Collection<int, string>
     */
    private function publicacionesDeLaEmpresa(): Collection
    {
        $empresaId = $this->empresaId();

        if ($empresaId === null) {
            return collect();
        }

        return once(fn (): Collection => Publicacion::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('cargo')
            ->pluck('cargo', 'id'));
    }

    #[Title('Mis favoritos · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $empresaId = $this->empresaId();

        // Se parte del conjunto de favoritos y se sube a los candidatos: así el filtro
        // por búsqueda reutiliza exactamente la misma definición de "favorito vigente".
        $query = Postulante::query()
            ->where('visible', true)
            ->whereIn('id', $this->favoritosDeLaEmpresa()->select('postulante_id'));

        $totalFavoritos = (clone $query)->count();

        // Filtro por la búsqueda donde se marcó el favorito.
        if ($this->busqueda !== 'todas') {
            $query->whereIn('id', $this->favoritosDeLaEmpresa()
                ->where('busqueda_id', (int) $this->busqueda)
                ->select('postulante_id'));
        }

        // Filtro por publicación asociada (o por no tener ninguna).
        if ($this->publicacion === 'sin') {
            $query->whereDoesntHave('publicacionesAsociadas', fn (Builder $q) => $q->where('publicaciones.empresa_id', $empresaId));
        } elseif ($this->publicacion !== 'todas') {
            $query->whereHas('publicacionesAsociadas', fn (Builder $q) => $q->where('publicaciones.id', (int) $this->publicacion));
        }

        // Filtro por desbloqueo del perfil.
        $desbloqueados = Desbloqueo::query()->where('empresa_id', $empresaId)->select('postulante_id');

        if ($this->desbloqueo === 'desbloqueados') {
            $query->whereIn('id', $desbloqueados);
        } elseif ($this->desbloqueo === 'bloqueados') {
            $query->whereNotIn('id', $desbloqueados);
        }

        $candidatos = $query
            ->with([
                'user',
                // Solo las marcas de favorito de esta empresa, con su búsqueda para los chips.
                'matches' => fn ($q) => $q->confirmados()
                    ->where('favorito', true)
                    ->whereHas('busqueda', fn (Builder $b) => $b->where('empresa_id', $empresaId))
                    ->with('busqueda:id,titulo'),
                'publicacionesAsociadas' => fn ($q) => $q->where('publicaciones.empresa_id', $empresaId),
            ])
            ->orderBy('id')
            ->paginate(15, pageName: 'favoritos');

        $empresa = auth()->user()->empresa;

        return view('livewire.empresa.favoritos', [
            'candidatos' => $candidatos,
            'totalFavoritos' => $totalFavoritos,
            'busquedasDisponibles' => $this->busquedasDeLaEmpresa(),
            'publicacionesDisponibles' => $this->publicacionesDeLaEmpresa(),
            'postulantesDesbloqueados' => Desbloqueo::query()
                ->where('empresa_id', $empresaId)
                ->whereIn('postulante_id', $candidatos->pluck('id'))
                ->pluck('postulante_id')
                ->all(),
            'hayFiltros' => $this->busqueda !== 'todas' || $this->publicacion !== 'todas' || $this->desbloqueo !== 'todos',
            'planVigente' => $empresa?->planVigente() ?? false,
            'publicacionesAsociables' => $this->publicacionesAsociables(),
            'publicacionesDelCandidato' => $this->publicacionesDelCandidato(),
        ]);
    }
}
