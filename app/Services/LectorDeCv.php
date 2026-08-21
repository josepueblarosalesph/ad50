<?php

namespace App\Services;

/**
 * Quien traduce el PDF a la estructura cruda del extractor.
 *
 * Se separa de [ExtractorCv] por dos razones: todo lo que rodea al modelo —validación
 * del archivo, saneamiento de la salida, mapeo a los catálogos— se puede probar sin
 * llamar a ninguna API, y cambiar de proveedor es cambiar una línea de configuración.
 *
 * Las implementaciones ([LectorDeCvClaude], [LectorDeCvGemini]) comparten instrucciones
 * y esquema de salida en [\App\Support\EsquemaCv], así que todas devuelven la misma
 * forma y quien las consume no necesita saber cuál está activa.
 */
interface LectorDeCv
{
    /**
     * @param  string  $pdf  Contenido binario del PDF ya validado.
     * @return array<string, mixed> JSON decodificado, sin sanear.
     *
     * @throws ExtraccionCvException
     */
    public function leer(string $pdf): array;

    /** Hay credenciales configuradas para este proveedor. */
    public function disponible(): bool;

    /** Identificador corto del proveedor, para la auditoría. */
    public function nombre(): string;

    /** Modelo con el que se está leyendo, para la auditoría. */
    public function modelo(): string;
}
