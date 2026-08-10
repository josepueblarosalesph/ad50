<?php

namespace App\Concerns;

use App\Models\CarpetaFavoritos;
use App\Models\Empresa;
use App\Models\Favorito;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Agrupa los candidatos guardados en carpetas que el reclutador crea a su gusto.
 *
 * Las carpetas son de quien las crea, no de la empresa (ver [CarpetaFavoritos]), así que
 * todo lo que hay aquí se acota por `auth()->id()`: dos personas del mismo equipo pueden
 * agrupar los mismos favoritos de formas distintas sin verse entre sí.
 *
 * Agrupar no es mover: un candidato puede estar en varias carpetas a la vez, y quitarlo
 * de todas no lo saca de favoritos.
 *
 * El componente que use el trait declara de qué empresa se trata y cómo resolver el
 * favorito de un candidato.
 */
trait OrganizaFavoritosEnCarpetas
{
    /** Postulante cuyo panel de carpetas está abierto; null = cerrado. */
    public ?int $organizandoPostulanteId = null;

    /** Nombre escrito en el campo de "nueva carpeta". */
    public string $nuevaCarpeta = '';

    /** Carpeta que se está renombrando; null = ninguna. */
    public ?int $carpetaEnEdicionId = null;

    public string $nombreEnEdicion = '';

    /**
     * Memo por petición de las carpetas con su conteo. No se usa once() porque en una
     * misma petición se consulta antes y después de escribir (crear, mover, borrar), y
     * el conteo memoizado quedaría desfasado al renderizar.
     *
     * @var Collection<int, CarpetaFavoritos>|null
     */
    private ?Collection $carpetasMemo = null;

    /** Empresa dueña de los favoritos que se agrupan. */
    abstract protected function empresaDeCarpetas(): ?Empresa;

    /** Favorito vigente del candidato en esa empresa, o null si ya no está guardado. */
    abstract protected function favoritoDeCandidato(int $postulanteId): ?Favorito;

    public function crearCarpeta(): void
    {
        $empresa = $this->empresaDeCarpetas();

        abort_if($empresa === null, 403);

        if ($this->carpetasDelUsuario()->count() >= CarpetaFavoritos::MAXIMO_POR_USUARIO) {
            $this->addError('nuevaCarpeta', 'Llegaste al máximo de '.CarpetaFavoritos::MAXIMO_POR_USUARIO.' carpetas.');

            return;
        }

        $this->validate(
            ['nuevaCarpeta' => $this->reglaNombre()],
            attributes: ['nuevaCarpeta' => 'nombre de la carpeta'],
        );

        CarpetaFavoritos::query()->create([
            'empresa_id' => $empresa->id,
            'user_id' => auth()->id(),
            'nombre' => trim($this->nuevaCarpeta),
        ]);

        $this->nuevaCarpeta = '';
        $this->olvidarCarpetas();
    }

    public function editarCarpeta(int $carpetaId): void
    {
        $carpeta = $this->carpetaPropia($carpetaId);

        $this->resetValidation();
        $this->carpetaEnEdicionId = $carpeta->id;
        $this->nombreEnEdicion = $carpeta->nombre;
    }

    public function cancelarEdicionCarpeta(): void
    {
        $this->resetValidation();
        $this->carpetaEnEdicionId = null;
        $this->nombreEnEdicion = '';
    }

    public function renombrarCarpeta(): void
    {
        abort_if($this->carpetaEnEdicionId === null, 404);

        $carpeta = $this->carpetaPropia($this->carpetaEnEdicionId);

        $this->validate(
            ['nombreEnEdicion' => $this->reglaNombre($carpeta->id)],
            attributes: ['nombreEnEdicion' => 'nombre de la carpeta'],
        );

        $carpeta->update(['nombre' => trim($this->nombreEnEdicion)]);

        $this->cancelarEdicionCarpeta();
        $this->olvidarCarpetas();
    }

    /**
     * Borra la carpeta. Los candidatos que agrupaba siguen en favoritos: se cae el
     * pivote (por la FK en cascada), no la estrella.
     */
    public function eliminarCarpeta(int $carpetaId): void
    {
        $this->carpetaPropia($carpetaId)->delete();

        if ($this->carpetaEnEdicionId === $carpetaId) {
            $this->cancelarEdicionCarpeta();
        }

        $this->olvidarCarpetas();
        $this->carpetaEliminada($carpetaId);
    }

    /**
     * Gancho para que el componente suelte lo que apuntaba a la carpeta recién borrada
     * (por ejemplo, si era la que estaba viendo). No-op por omisión.
     */
    protected function carpetaEliminada(int $carpetaId): void
    {
        //
    }

    public function abrirCarpetas(int $postulanteId): void
    {
        abort_if($this->favoritoDeCandidato($postulanteId) === null, 404);

        $this->organizandoPostulanteId = $postulanteId;
        $this->modal('organizar-carpetas')->show();
    }

    public function cerrarCarpetas(): void
    {
        $this->organizandoPostulanteId = null;
        $this->modal('organizar-carpetas')->close();
    }

    /** Agrega o saca de la carpeta al candidato que tiene el panel abierto. */
    public function alternarCarpeta(int $carpetaId): void
    {
        abort_if($this->organizandoPostulanteId === null, 404);

        $favorito = $this->favoritoDeCandidato($this->organizandoPostulanteId);

        abort_if($favorito === null, 404);

        $this->carpetaPropia($carpetaId)->favoritos()->toggle($favorito->id);

        $this->olvidarCarpetas();
    }

    /**
     * Carpetas del usuario con cuántos candidatos visibles agrupa cada una.
     *
     * El conteo excluye a los postulantes que pausaron su perfil, para que no prometa
     * más candidatos de los que el listado va a mostrar.
     *
     * @return Collection<int, CarpetaFavoritos>
     */
    protected function carpetasDelUsuario(): Collection
    {
        if ($this->carpetasMemo !== null) {
            return $this->carpetasMemo;
        }

        $empresa = $this->empresaDeCarpetas();

        if ($empresa === null) {
            return collect();
        }

        return $this->carpetasMemo = CarpetaFavoritos::query()
            ->where('user_id', auth()->id())
            ->where('empresa_id', $empresa->id)
            ->withCount(['favoritos' => fn (Builder $query) => $query
                ->whereHas('postulante', fn (Builder $postulante) => $postulante->where('visible', true))])
            ->orderBy('nombre')
            ->get();
    }

    /**
     * IDs de las carpetas en las que ya está el candidato abierto en el panel.
     *
     * @return list<int>
     */
    protected function carpetasDelCandidato(): array
    {
        if ($this->organizandoPostulanteId === null) {
            return [];
        }

        $favorito = $this->favoritoDeCandidato($this->organizandoPostulanteId);

        if ($favorito === null) {
            return [];
        }

        return $favorito->carpetas()
            ->where('carpetas_favoritos.user_id', auth()->id())
            ->pluck('carpetas_favoritos.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Nombres de las carpetas de cada postulante, para las etiquetas del listado.
     * Se resuelve en una consulta para toda la página, no una por tarjeta.
     *
     * @param  iterable<int>  $postulanteIds
     * @return array<int, list<string>>
     */
    protected function carpetasPorPostulante(iterable $postulanteIds): array
    {
        $ids = collect($postulanteIds)->all();
        $empresa = $this->empresaDeCarpetas();

        if ($ids === [] || $empresa === null) {
            return [];
        }

        return Favorito::query()
            ->join('carpeta_favorito', 'carpeta_favorito.favorito_id', '=', 'favoritos.id')
            ->join('carpetas_favoritos', 'carpetas_favoritos.id', '=', 'carpeta_favorito.carpeta_id')
            ->where('favoritos.empresa_id', $empresa->id)
            ->where('carpetas_favoritos.user_id', auth()->id())
            ->whereIn('favoritos.postulante_id', $ids)
            ->orderBy('carpetas_favoritos.nombre')
            ->get(['favoritos.postulante_id', 'carpetas_favoritos.nombre'])
            ->groupBy('postulante_id')
            ->map(fn (Collection $filas): array => $filas->pluck('nombre')->all())
            ->all();
    }

    /**
     * Carpeta del usuario autenticado. 404 si es de otra persona o de otra empresa:
     * el id llega desde el cliente y no se puede confiar en él.
     */
    private function carpetaPropia(int $carpetaId): CarpetaFavoritos
    {
        $empresa = $this->empresaDeCarpetas();

        abort_if($empresa === null, 403);

        $carpeta = CarpetaFavoritos::query()
            ->where('id', $carpetaId)
            ->where('user_id', auth()->id())
            ->where('empresa_id', $empresa->id)
            ->first();

        abort_if($carpeta === null, 404);

        return $carpeta;
    }

    /**
     * @return list<mixed>
     */
    private function reglaNombre(?int $ignorarId = null): array
    {
        return [
            'required',
            'string',
            'max:40',
            Rule::unique('carpetas_favoritos', 'nombre')
                ->where('user_id', auth()->id())
                ->ignore($ignorarId),
        ];
    }

    /** Tras escribir, el conteo memoizado deja de valer. */
    private function olvidarCarpetas(): void
    {
        $this->carpetasMemo = null;
    }
}
