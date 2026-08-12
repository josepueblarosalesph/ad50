<?php

namespace App\Livewire\Empresa;

use App\Concerns\AsociaCandidatosAPublicaciones;
use App\Concerns\OrganizaFavoritosEnCarpetas;
use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Desbloqueo;
use App\Models\Empresa;
use App\Models\Favorito;
use App\Models\NotaCandidato;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Services\MatchingService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
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

    /** Publicación asociada: 'todas', 'sin' (sin asociar) o el id de una publicación. */
    #[Url(history: true)]
    public string $publicacion = 'todas';

    /** Estado de desbloqueo: 'todos', 'desbloqueados' o 'bloqueados'. */
    #[Url(history: true)]
    public string $desbloqueo = 'todos';

    /** Candidato cuyas notas están abiertas en el panel rápido; null = cerrado. */
    public ?int $notasPostulanteId = null;

    /**
     * Criterios de perfil del panel lateral, los mismos de Prospección de Candidatos y
     * del listado de una publicación. Null = sin filtrar.
     *
     * @var array<string, mixed>|null
     */
    public ?array $criterios = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        // Se valida contra todas las publicaciones (incluidas las cerradas): filtrar por
        // una cerrada es legítimo, aunque no se pueda asociar candidatos nuevos a ella.
        if ($this->publicacion !== 'todas' && $this->publicacion !== 'sin'
            && ! $this->publicacionesDeLaEmpresa()->has((int) $this->publicacion)) {
            $this->publicacion = 'todas';
        }

        if (! in_array($this->desbloqueo, ['todos', 'desbloqueados', 'bloqueados'], true)) {
            $this->desbloqueo = 'todos';
        }

        // Una carpeta ajena o ya borrada equivale a no filtrar por carpeta. Con la
        // funcionalidad apagada se ignora cualquier valor: si no, un `?carpeta=sin`
        // en la URL delataría en el encabezado una función que no debería verse.
        if (! $this->carpetasHabilitadas()) {
            $this->carpeta = 'todas';
        } elseif ($this->carpeta !== 'todas' && $this->carpeta !== 'sin'
            && ! $this->carpetasDelUsuario()->contains('id', (int) $this->carpeta)) {
            $this->carpeta = 'todas';
        }
    }

    public function updated(string $campo): void
    {
        if (in_array($campo, ['publicacion', 'desbloqueo'], true)) {
            $this->resetPage(pageName: 'favoritos');
        }
    }

    /**
     * La carpeta activa la elige el panel de la barra lateral, que es un componente
     * aparte ([CarpetasFavoritos]): el layout pinta ese slot fuera de esta raíz, así que
     * un wire:click puesto ahí no llegaría nunca. Se comunican por evento.
     */
    #[On('carpeta-seleccionada')]
    public function verCarpeta(string $carpeta): void
    {
        abort_unless($this->carpetasHabilitadas(), 404);
        abort_unless(
            in_array($carpeta, ['todas', 'sin'], true) || $this->carpetasDelUsuario()->contains('id', (int) $carpeta),
            404,
        );

        $this->carpeta = $carpeta;
        $this->resetPage(pageName: 'favoritos');
    }

    /** El "+" del panel lateral pide abrir el popup, que se monta aquí. */
    #[On('abrir-nueva-carpeta')]
    public function abrirPopupNuevaCarpeta(): void
    {
        $this->abrirNuevaCarpeta();
    }

    /** Cambió un nombre o se borró una carpeta: basta con repintar las etiquetas. */
    #[On('carpetas-cambiaron')]
    public function refrescarCarpetas(): void
    {
        $this->olvidarCarpetas();
    }

    public function limpiarFiltros(): void
    {
        $this->publicacion = 'todas';
        $this->desbloqueo = 'todos';
        $this->resetPage(pageName: 'favoritos');
    }

    /**
     * Criterios que llegan del panel lateral. Es el mismo evento y el mismo panel que
     * usa el listado de una publicación: no se duplica el formulario de filtros.
     *
     * @param  array<string, mixed>  $criterios
     */
    #[On('criterios-postulaciones')]
    public function filtrar(array $criterios): void
    {
        $this->criterios = $criterios;
        $this->resetPage(pageName: 'favoritos');
    }

    /**
     * Evalúa el perfil contra los criterios del panel con el mismo motor del matching,
     * así que filtrar aquí significa exactamente lo mismo que en una búsqueda.
     */
    private function cumpleCriterios(Postulante $postulante): bool
    {
        $criterios = $this->criterios ?? [];

        if ($criterios === []) {
            return true;
        }

        $detalle = app(MatchingService::class)->evaluar($postulante, $criterios);

        return ! collect($detalle)->contains(fn (array $criterio): bool => ! $criterio['cumple']);
    }

    /** Abre la vista rápida de las notas. Es solo lectura: escribirlas vive en la ficha. */
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
            ->where('empresa_id', $this->empresaId())
            ->where('postulante_id', $this->notasPostulanteId)
            ->visiblesPara(auth()->user())
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Ficha del candidato abierto en el panel, para que pueda escribir o editar la suya:
     * el panel es solo lectura y el formulario vive allá. Null si no tiene coincidencia
     * en ninguna búsqueda de la empresa (el favorito no depende de ellas).
     */
    private function urlPerfilDeNotas(): ?string
    {
        if ($this->notasPostulanteId === null) {
            return null;
        }

        $match = BusquedaCandidato::query()
            ->where('postulante_id', $this->notasPostulanteId)
            ->where('temporal', false)
            // Con papelera, por lo mismo que el enlace del listado: el favorito sobrevive
            // a que su búsqueda se elimine.
            ->whereIn('busqueda_id', Busqueda::query()->withTrashed()->where('empresa_id', $this->empresaId())->select('id'))
            ->first();

        return $match === null
            ? null
            : route('empresa.candidatos.show', ['match' => $match, 'origen' => 'favoritos']).'#notas';
    }

    /**
     * Nota a mostrar en cada tarjeta del listado, resuelta en una sola consulta para
     * toda la página.
     *
     * Un candidato puede tener varias notas legibles (la propia más las que el equipo
     * comparte) y en la tarjeta solo cabe una: se prefiere **la propia**, porque es lo
     * que quien mira escribió y quiere recordar; si no tiene, la más reciente del
     * equipo. El resto se cuenta para el "+N" que lleva al panel completo.
     *
     * @param  iterable<int>  $postulanteIds
     * @return array<int, array{contenido: string, autor: string|null, privada: bool, otras: int}>
     */
    private function notasDeLasTarjetas(iterable $postulanteIds): array
    {
        $ids = collect($postulanteIds)->all();

        if ($ids === []) {
            return [];
        }

        $notas = NotaCandidato::query()
            ->where('empresa_id', $this->empresaId())
            ->whereIn('postulante_id', $ids)
            ->visiblesPara(auth()->user())
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->get();

        $resumen = [];

        foreach ($notas->groupBy('postulante_id') as $postulanteId => $delCandidato) {
            $propia = $delCandidato->firstWhere('user_id', auth()->id());
            $elegida = $propia ?? $delCandidato->first();

            $resumen[(int) $postulanteId] = [
                'contenido' => (string) $elegida->contenido,
                // El autor solo se rotula cuando no es de quien mira: en su propia nota
                // el nombre no aporta y roba el espacio que necesita el texto.
                'autor' => $elegida->user_id === auth()->id() ? null : $elegida->autorLabel(),
                'privada' => $elegida->esPrivada(),
                'otras' => $delCandidato->count() - 1,
            ];
        }

        return $resumen;
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
            ->whereIn('busqueda_id', Busqueda::query()->withTrashed()->where('empresa_id', $empresa->id)->select('id'))
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

    /**
     * Asignar o quitar una carpeta mueve los conteos del panel lateral, que vive en otro
     * componente: hay que avisarle para que los rehaga.
     */
    protected function avisarCambio(): void
    {
        $this->dispatch('refrescar-carpetas');
    }

    /**
     * Sin filtro por búsqueda, una asociación hecha desde Favoritos no tiene una búsqueda
     * de origen que anotar: aquí se parte de la lista de guardados, no de un calce.
     */
    protected function busquedaDeAsociacion(): ?int
    {
        return null;
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
            // perfil. Cuentan también las de búsquedas en la papelera: el favorito es de
            // la cuenta y sobrevive a que su búsqueda se elimine, así que exigir una
            // búsqueda vigente dejaba sin botón de perfil a gente ya guardada —incluso
            // desbloqueada— solo porque su búsqueda de origen ya no está.
            ->addSelect(['match_visible_id' => BusquedaCandidato::query()
                ->select('busqueda_candidato.id')
                ->whereColumn('busqueda_candidato.postulante_id', 'postulantes.id')
                ->whereIn('busqueda_candidato.busqueda_id', Busqueda::query()->withTrashed()->where('empresa_id', $empresaId)->select('id'))
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

        // Los criterios del panel se evalúan con el motor de matching, que trabaja sobre
        // la ficha completa y no sobre columnas: hay que traer las filas y filtrarlas en
        // memoria, igual que hace el listado de una publicación. Se puede porque los
        // favoritos de una cuenta son decenas —se marcan a mano—, no un catálogo entero.
        $todos = $query
            ->with([
                'user',
                'publicacionesAsociadas' => fn ($q) => $q->where('publicaciones.empresa_id', $empresaId),
            ])
            ->orderBy('id')
            ->get()
            ->filter(fn (Postulante $postulante): bool => $this->cumpleCriterios($postulante))
            ->values();

        $totalFiltrados = $todos->count();
        $paginaActual = Paginator::resolveCurrentPage('favoritos');

        $candidatos = new LengthAwarePaginator(
            $todos->forPage($paginaActual, 15)->values(),
            $totalFiltrados,
            15,
            $paginaActual,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'favoritos'],
        );

        $empresa = auth()->user()->empresa;

        return view('livewire.empresa.favoritos', [
            'candidatos' => $candidatos,
            'totalFavoritos' => $totalFavoritos,
            'publicacionesDisponibles' => $this->publicacionesDeLaEmpresa(),
            'postulantesDesbloqueados' => Desbloqueo::query()
                ->where('empresa_id', $empresaId)
                ->whereIn('postulante_id', $candidatos->pluck('id'))
                ->pluck('postulante_id')
                ->all(),
            'notasPorCandidato' => $this->notasDeLasTarjetas($candidatos->pluck('id')),
            'notasDelCandidato' => $this->notasDelCandidato(),
            'notasPerfilUrl' => $this->urlPerfilDeNotas(),
            'carpetasVisibles' => $this->carpetasHabilitadas(),
            'carpetas' => $this->carpetasDelUsuario(),
            'carpetaActiva' => $this->carpetasDelUsuario()->firstWhere('id', (int) $this->carpeta),
            'carpetasPorCandidato' => $this->carpetasPorPostulante($candidatos->pluck('id')),
            'carpetasDelCandidato' => $this->carpetasDelCandidato(),
            'sinCarpeta' => $this->favoritosDeLaEmpresa()
                ->whereDoesntHave('carpetas', fn (Builder $q) => $q->where('carpetas_favoritos.user_id', auth()->id()))
                ->count(),
            'totalFiltrados' => $totalFiltrados,
            'hayCriterios' => ($this->criterios ?? []) !== [],
            'hayFiltros' => $this->publicacion !== 'todas' || $this->desbloqueo !== 'todos'
                || ($this->criterios ?? []) !== [],
            'planVigente' => $empresa?->planVigente() ?? false,
            'desbloqueosDisponibles' => $empresa?->desbloqueosDisponibles() ?? 0,
            'publicacionesAsociables' => $this->publicacionesAsociables(),
            'publicacionesDelCandidato' => $this->publicacionesDelCandidato(),
        ]);
    }
}
