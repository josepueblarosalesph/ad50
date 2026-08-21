<?php

namespace App\Support;

/**
 * Lo que devuelve una extracción de CV: los campos listos para la ficha y con qué
 * cuidado hay que mirarlos.
 *
 * Nada de esto se persiste solo. El postulante ve los campos en el formulario y los
 * guarda él: esa confirmación es la que convierte cualquier falla de las capas
 * anteriores en un error visible y corregible, y no en un dato envenenado en la base.
 */
final readonly class ResultadoCv
{
    /**
     * @param  array<string, mixed>  $datos  Campos con los nombres del componente Livewire.
     * @param  'alta'|'media'|'baja'  $confianza
     * @param  array<int, string>  $flags  Señales de seguridad detectadas en el documento.
     * @param  array<int, string>  $notas  Avisos legibles para la persona (fechas ilegibles, etc.).
     */
    public function __construct(
        public array $datos,
        public string $confianza,
        public array $flags = [],
        public array $notas = [],
    ) {}

    /**
     * Hay algo que la persona debe revisar con más atención de la habitual.
     */
    public function requiereRevision(): bool
    {
        return $this->flags !== [] || $this->confianza === 'baja';
    }
}
