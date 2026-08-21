<?php

namespace App\Services;

use RuntimeException;

/**
 * Falla de la extracción del CV cuyo mensaje se le muestra tal cual al postulante.
 *
 * Nunca lleva contenido del documento: solo el motivo y qué puede hacer la persona.
 */
class ExtraccionCvException extends RuntimeException
{
    public static function archivoNoEsPdf(): self
    {
        return new self('El archivo no parece un PDF válido. Vuelve a exportarlo e inténtalo de nuevo.');
    }

    public static function demasiadoGrande(): self
    {
        return new self('El archivo supera los 10 MB. Sube una versión más liviana.');
    }

    public static function demasiadasPaginas(int $maximo): self
    {
        return new self("El documento tiene más de $maximo páginas. Sube una versión más breve de tu CV.");
    }

    public static function contieneElementosActivos(): self
    {
        return new self('El archivo contiene elementos activos (formularios, scripts o adjuntos). Ábrelo y vuelve a guardarlo como PDF impreso.');
    }

    public static function servicioNoDisponible(): self
    {
        return new self('El autocompletado no está disponible en este momento. Puedes llenar tu ficha a mano y subir el CV al final.');
    }

    /**
     * Se separa del caso anterior porque la acción de la persona es distinta: aquí no
     * hay nada roto, solo hay que volver más tarde.
     */
    public static function cuotaAgotada(): self
    {
        return new self('Hemos alcanzado el límite de lecturas de CV por ahora. Inténtalo en unos minutos o llena tu ficha a mano.');
    }

    public static function respuestaIlegible(): self
    {
        return new self('No pudimos leer el documento. Revisa que el PDF tenga texto seleccionable y vuelve a intentarlo, o llena tu ficha a mano.');
    }
}
