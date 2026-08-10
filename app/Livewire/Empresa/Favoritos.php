<?php

namespace App\Livewire\Empresa;

use App\Concerns\AsociaCandidatosAPublicaciones;
use App\Concerns\OrganizaFavoritosEnCarpetas;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Desbloqueo;
use App\Models\Empresa;
use App\Models\Favorito;
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
 * Todos los candidatos que la empresa guardó, en una sola lista.
 *
 * El favorito es de la cuenta (tabla `favoritos`), así que hay uno por candidato sin
 * importar desde qué búsqueda se marcó. `busqueda_id` guarda ese origen y alimenta el
 * filtro "Búsqueda de origen".
 *
 * Sobre esa lista compartida, cada usuario del equipo arma sus propias carpetas para
 * agrupar perfiles (ver [OrganizaFavoritosEnCarpetas]).
 */
class Favoritos extends Component
{
    use AsociaCandidatosAPublicaciones;
    use OrganizaFavoritosEnCarpetas;
    use WithPagination;

    /** Carpeta activa: 'todas', 'sin' (sin carpeta) o el id de una carpeta propia. */
    #[Url(history: true)]
    public string $carpeta = 'todas';

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

        // Una carpeta ajena o ya borrada equivale a no filtrar por carpeta.
        if ($this->carpeta !== 'todas' && $this->carpeta !== 'sin'
            && ! $this->carpetasDelUsuario()->contains('id', (int) $this->carpeta)) {
            $this->carpeta = 'todas';
        }
    }

    public function updated(string $campo): void
    {
        if (in_array($campo, ['busqueda', 'publicacion', 'desbloqueo'], true)) {
            $this->resetPage(pageName: 'favoritos');
        }
    }

    /** Cambia la carpeta activa desde la barra lateral. */
    public function verCarpeta(string $carpeta): void
    {
        abort_unless(
            in_array($carpeta, ['todas', 'sin'], true) || $this->carpetasDelUsuario()->contains('id', (int) $carpeta),
            404,
        );

        $this->carpeta = $carpeta;
        $this->resetPage(pageName: 'favoritos');
    }

    public function limpiarFiltros(): void
    {
        $this->busqueda = 'todas';
        $this->publicacion = 'todas';
        $this->desbloqueo = 'todos';
        $this->resetPage(pageName: 'favoritos');
    }

    /** Quita al candidato de los favoritos de la empresa. */
    public function quitarFavorito(int $postulanteId): void
    {
        $favorito = $this->favoritosDeLaEmpresa()->where('postulante_id', $postulanteId)->first();

        abort_if($favorito === null, 404);

        $favorito->delete();
        $this->resetPage(pageName: 'favoritos');
    }

    /**
     * Desbloquea el perfil de un favorito consumiendo un cupo del plan. Espeja la lógica
     * de Resultados::desbloquear; los errores se informan por flash.
     */
    public function desbloquear(int $postulanteId): void
    {
        $empresa = auth()->user()->empresa;

        abort_unless(auth()->user()->role === 'empresa' && $empresa !== null, 403);
        abort_unless($this->favoritosDeLaEmpresa()->where('postulante_id', $postulanteId)->exists(), 404);

        if ($empresa->haDesbloqueado($postulanteId)) {
            return;
        }

        if (! $empresa->planVigente()) {
            session()->flash('desbloqueo_error', 'Necesitas una suscripción activa para desbloquear perfiles.');

            return;
        }

        if ($empresa->desbloqueosDisponibles() < 1) {
            session()->flash('desbloqueo_error', 'No te quedan desbloqueos disponibles en tu plan.');

            return;
        }

        $empresa->desbloqueos()->create(['postulante_id' => $postulanteId]);

        // Deja la marca de contacto en la coincidencia por la que se abre su perfil, igual
        // que al desbloquear desde la búsqueda. Un favorito puede no tener ninguna.
        BusquedaCandidato::query()
            ->where('postulante_id', $postulanteId)
            ->whereNull('contactado_at')
            ->where('temporal', false)
            ->whereIn('busqueda_id', Busqueda::query()->where('empresa_id', $empresa->id)->select('id'))
            ->update(['contactado_at' => now()]);
    }

    protected function empresaDeAsociacion(): ?Empresa
    {
        return auth()->user()->empresa;
    }

    protected function empresaDeCarpetas(): ?Empresa
    {
        return auth()->user()->empresa;
    }

    protected function favoritoDeCandidato(int $postulanteId): ?Favorito
    {
        return $this->favoritosDeLaEmpresa()->where('postulante_id', $postulanteId)->first();
    }

    /** Si se borró la carpeta que se estaba viendo, se vuelve a la lista completa. */
    protected function carpetaEliminada(int $carpetaId): void
    {
        if ($this->carpeta === (string) $carpetaId) {
            $this->carpeta = 'todas';
            $this->resetPage(pageName: 'favoritos');
        }
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
     * Favoritos de la empresa sobre postulantes visibles.
     *
     * @return Builder<Favorito>
     */
    private function favoritosDeLaEmpresa(): Builder
    {
        return Favorito::query()
            ->where('empresa_id', $this->empresaId())
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

        $query = Postulante::query()
            ->select('postulantes.*')
            // Una coincidencia cualquiera del candidato en esta empresa, para enlazar al
            // perfil. Puede no existir: el favorito ya no depende de ninguna búsqueda.
            ->addSelect(['match_visible_id' => BusquedaCandidato::query()
                ->select('busqueda_candidato.id')
                ->whereColumn('busqueda_candidato.postulante_id', 'postulantes.id')
                ->whereIn('busqueda_candidato.busqueda_id', Busqueda::query()->where('empresa_id', $empresaId)->select('id'))
                ->where('busqueda_candidato.temporal', false)
                ->limit(1),
            ])
            ->where('visible', true)
            ->whereIn('id', $this->favoritosDeLaEmpresa()->select('postulante_id'));

        $totalFavoritos = (clone $query)->count();

        // Carpeta activa. Es navegación, no filtro: vive en la barra lateral y "Limpiar
        // filtros" no la toca, igual que cambiar de carpeta no borra los filtros.
        if ($this->carpeta === 'sin') {
            $query->whereIn('id', $this->favoritosDeLaEmpresa()
                ->whereDoesntHave('carpetas', fn (Builder $q) => $q->where('carpetas_favoritos.user_id', auth()->id()))
                ->select('postulante_id'));
        } elseif ($this->carpeta !== 'todas') {
            $query->whereIn('id', $this->favoritosDeLaEmpresa()
                ->whereHas('carpetas', fn (Builder $q) => $q->where('carpetas_favoritos.id', (int) $this->carpeta))
                ->select('postulante_id'));
        }

        // Filtro por la búsqueda desde la que se guardó.
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
            'carpetas' => $this->carpetasDelUsuario(),
            'carpetaActiva' => $this->carpetasDelUsuario()->firstWhere('id', (int) $this->carpeta),
            'carpetasPorCandidato' => $this->carpetasPorPostulante($candidatos->pluck('id')),
            'carpetasDelCandidato' => $this->carpetasDelCandidato(),
            'sinCarpeta' => $this->favoritosDeLaEmpresa()
                ->whereDoesntHave('carpetas', fn (Builder $q) => $q->where('carpetas_favoritos.user_id', auth()->id()))
                ->count(),
            'hayFiltros' => $this->busqueda !== 'todas' || $this->publicacion !== 'todas' || $this->desbloqueo !== 'todos',
            'planVigente' => $empresa?->planVigente() ?? false,
            'desbloqueosDisponibles' => $empresa?->desbloqueosDisponibles() ?? 0,
            'publicacionesAsociables' => $this->publicacionesAsociables(),
            'publicacionesDelCandidato' => $this->publicacionesDelCandidato(),
        ]);
    }
}
