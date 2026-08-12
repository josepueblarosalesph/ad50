<?php

namespace App\Concerns;

use App\Models\Postulacion;
use App\Models\Publicacion;

/**
 * Permite postular a una oferta desde cualquier pantalla del portal del postulante
 * (panel, listado de oportunidades y detalle de una publicación).
 *
 * El flujo es el mismo en las tres: se abre el modal con las preguntas de la oferta,
 * se responden y se guarda la postulación.
 */
trait PostulaAOfertas
{
    /** Publicación cuyo formulario de postulación está abierto; null = cerrado. */
    public ?int $postulandoId = null;

    /** @var array<int, string> */
    public array $respuestas = [];

    public function abrirPostulacion(Publicacion $publicacion): void
    {
        abort_unless($publicacion->estaVigente(), 404);

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
                'estado' => 'recibida',
            ],
        );

        $this->reset('postulandoId', 'respuestas');
        $this->modal('postular-publicacion')->close();
        session()->flash('status', 'Tu postulación fue enviada correctamente.');
    }

    /** Publicación que se está postulando, para pintar el modal. */
    protected function publicacionEnPostulacion(): ?Publicacion
    {
        return $this->postulandoId === null
            ? null
            : Publicacion::query()->find($this->postulandoId);
    }
}
