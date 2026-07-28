<?php

namespace App\Concerns;

use App\Models\Empresa;
use App\Models\Publicacion;
use App\Models\PublicacionCandidato;
use Illuminate\Support\Collection;

/**
 * Asocia un candidato encontrado en el Talent Finder a una o más publicaciones.
 *
 * Es una acción manual del reclutador, independiente del favorito y del matching: la
 * asociación vive en `publicacion_candidato` y sobrevive a que el candidato deje de
 * cumplir los criterios de la búsqueda desde la que se asoció.
 *
 * El componente que use el trait debe declarar de qué empresa y búsqueda se trata, y
 * qué candidatos puede asociar.
 */
trait AsociaCandidatosAPublicaciones
{
    /** Postulante cuyo panel de publicaciones está abierto; null = cerrado. */
    public ?int $asociandoPostulanteId = null;

    /** Empresa dueña de las publicaciones y de la búsqueda. */
    abstract protected function empresaDeAsociacion(): ?Empresa;

    /** Búsqueda desde la que se asocia, solo para trazabilidad. */
    abstract protected function busquedaDeAsociacion(): ?int;

    /** El candidato pertenece al conjunto que este componente puede asociar. */
    abstract protected function candidatoAsociable(int $postulanteId): bool;

    public function abrirAsociacion(int $postulanteId): void
    {
        abort_unless($this->candidatoAsociable($postulanteId), 404);

        $this->asociandoPostulanteId = $postulanteId;
        $this->modal('asociar-publicaciones')->show();
    }

    public function cerrarAsociacion(): void
    {
        $this->asociandoPostulanteId = null;
        $this->modal('asociar-publicaciones')->close();
    }

    /** Asocia o desasocia al candidato de una publicación de la empresa. */
    public function toggleAsociacion(int $publicacionId): void
    {
        $postulanteId = $this->asociandoPostulanteId;

        abort_if($postulanteId === null, 404);
        abort_unless($this->candidatoAsociable($postulanteId), 404);
        abort_unless($this->publicacionesAsociables()->contains('id', $publicacionId), 404);

        $existente = PublicacionCandidato::query()
            ->where('publicacion_id', $publicacionId)
            ->where('postulante_id', $postulanteId)
            ->first();

        if ($existente !== null) {
            $existente->delete();

            return;
        }

        PublicacionCandidato::query()->create([
            'publicacion_id' => $publicacionId,
            'postulante_id' => $postulanteId,
            'busqueda_id' => $this->busquedaDeAsociacion(),
        ]);
    }

    /**
     * Publicaciones de la empresa a las que se puede asociar un candidato.
     * Se excluyen las cerradas: ya no admiten movimiento de candidatos.
     *
     * @return Collection<int, Publicacion>
     */
    protected function publicacionesAsociables(): Collection
    {
        $empresa = $this->empresaDeAsociacion();

        if ($empresa === null) {
            return collect();
        }

        return once(fn (): Collection => Publicacion::query()
            ->whereBelongsTo($empresa)
            ->whereIn('estado', ['publicada', 'pausada'])
            ->orderBy('cargo')
            ->get(['id', 'cargo', 'estado', 'comuna']));
    }

    /**
     * IDs de las publicaciones a las que ya está asociado el candidato abierto.
     *
     * @return list<int>
     */
    protected function publicacionesDelCandidato(): array
    {
        if ($this->asociandoPostulanteId === null) {
            return [];
        }

        return array_values(
            PublicacionCandidato::query()
                ->where('postulante_id', $this->asociandoPostulanteId)
                ->whereIn('publicacion_id', $this->publicacionesAsociables()->pluck('id'))
                ->pluck('publicacion_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all()
        );
    }

    /**
     * Cuántas publicaciones tiene asociadas cada postulante, para los contadores del listado.
     *
     * @param  iterable<int>  $postulanteIds
     * @return array<int, int>
     */
    protected function conteoAsociaciones(iterable $postulanteIds): array
    {
        $ids = collect($postulanteIds)->all();

        if ($ids === []) {
            return [];
        }

        return PublicacionCandidato::query()
            ->whereIn('postulante_id', $ids)
            ->whereIn('publicacion_id', $this->publicacionesAsociables()->pluck('id'))
            ->selectRaw('postulante_id, count(*) as total')
            ->groupBy('postulante_id')
            ->pluck('total', 'postulante_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
}
