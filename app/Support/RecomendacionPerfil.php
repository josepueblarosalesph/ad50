<?php

namespace App\Support;

/**
 * Un ítem de la ficha del postulante, con lo que aporta a la completitud del perfil y
 * el texto con que se le sugiere completarlo.
 *
 * Es a la vez unidad de cálculo y de mensaje: el mismo objeto suma su `peso` cuando está
 * `cumplida` y, cuando no, se muestra como recomendación. Así no hay dos listas que
 * puedan desincronizarse (una para el porcentaje y otra para los consejos).
 *
 * `seccion` usa las mismas claves que `Ficha::editarSeccion()`, de modo que la vista
 * puede abrir directamente el formulario que corresponde a la recomendación.
 */
final class RecomendacionPerfil
{
    public function __construct(
        public readonly string $clave,
        public readonly string $seccion,
        public readonly string $titulo,
        public readonly string $detalle,
        public readonly int $peso,
        public readonly bool $cumplida,
        /** Lo que el asistente de bienvenida no deja saltar: sin esto la ficha no compite. */
        public readonly bool $obligatoria = false,
    ) {}

    /** Ancla de la sección dentro de "Mi perfil profesional". */
    public function ancla(): string
    {
        return match ($this->seccion) {
            'datos' => 'datos-personales',
            'acerca' => 'acerca-de-mi',
            'curriculum' => 'curriculum',
            default => $this->seccion,
        };
    }
}
