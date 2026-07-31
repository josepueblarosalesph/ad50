<?php

namespace App\Support;

use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\PublicacionCandidato;
use Carbon\CarbonInterface;

/**
 * Una persona en el listado de una publicación, venga de donde venga.
 *
 * A una publicación se llega por dos caminos distintos que viven en tablas distintas:
 * el postulante **postula** (fila en `postulaciones`, con estado y respuestas) o la
 * empresa lo **agrega** desde Prospección de Candidatos (fila en `publicacion_candidato`).
 * Los dos caminos no se excluyen: alguien agregado puede postular después.
 *
 * Esta clase junta ambos en una sola fila del listado y deja explícito el origen, para
 * que la vista no tenga que saber de qué tabla salió cada quien.
 */
final class CandidatoDePublicacion
{
    public function __construct(
        public readonly Postulante $postulante,
        public readonly ?Postulacion $postulacion,
        public readonly ?PublicacionCandidato $asociacion,
    ) {}

    /** Postuló por su cuenta desde el portal. */
    public function postulo(): bool
    {
        return $this->postulacion !== null;
    }

    /** La empresa lo agregó a la publicación desde Prospección de Candidatos. */
    public function agregado(): bool
    {
        return $this->asociacion !== null;
    }

    /** Estado de la postulación; null en quien solo fue agregado (no hay postulación que gestionar). */
    public function estado(): ?string
    {
        return $this->postulacion?->estado;
    }

    public function estadoLabel(): ?string
    {
        return $this->postulacion?->estadoLabel();
    }

    /**
     * Fecha que ordena el listado: la de la postulación cuando la hay, porque es el
     * hecho más relevante; si no, la de la asociación.
     */
    public function fecha(): ?CarbonInterface
    {
        return $this->postulacion->created_at ?? $this->asociacion->created_at ?? null;
    }

    /** Búsqueda desde la que se agregó, para trazar de dónde salió el candidato. */
    public function busquedaDeOrigen(): ?string
    {
        return $this->asociacion?->busqueda?->titulo;
    }

    /** Clave estable para `wire:key`: la fila es la persona, no la tabla de origen. */
    public function clave(): string
    {
        return 'candidato-'.$this->postulante->id;
    }
}
