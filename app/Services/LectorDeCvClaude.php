<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use Anthropic\Messages\TextBlock;
use App\Support\EsquemaCv;

/**
 * Lee el CV con Claude, mandando el PDF tal cual (bloque `document`) en lugar de texto
 * extraído: así aguanta currículums escaneados, a dos columnas o con tablas, que es
 * donde un extractor de texto en PHP se rompe.
 *
 * Las instrucciones y el esquema de salida viven en [EsquemaCv], compartidos con el
 * lector de Gemini.
 */
class LectorDeCvClaude implements LectorDeCv
{
    public function disponible(): bool
    {
        return filled(config('services.anthropic.api_key'));
    }

    public function nombre(): string
    {
        return 'claude';
    }

    public function modelo(): string
    {
        return (string) config('services.anthropic.modelo');
    }

    public function leer(string $pdf): array
    {
        if (! $this->disponible()) {
            throw ExtraccionCvException::servicioNoDisponible();
        }

        try {
            $mensaje = (new Client(apiKey: (string) config('services.anthropic.api_key')))->messages->create(
                model: $this->modelo(),
                maxTokens: 16000,
                system: EsquemaCv::INSTRUCCIONES,
                // El esfuerzo medio basta: transcribir y estructurar no es una tarea de
                // razonamiento profundo, y la persona está esperando la respuesta.
                outputConfig: ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => EsquemaCv::esquema()]],
                messages: [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'document', 'source' => [
                            'type' => 'base64',
                            'mediaType' => 'application/pdf',
                            'data' => base64_encode($pdf),
                        ]],
                        ['type' => 'text', 'text' => EsquemaCv::PETICION],
                    ],
                ]],
            );
        } catch (APIException $e) {
            report($e);

            throw ExtraccionCvException::servicioNoDisponible();
        }

        // Una negativa del clasificador o una respuesta cortada dejan un JSON incompleto:
        // mejor pedir carga manual que persistir campos a medias.
        if ($mensaje->stopReason !== 'end_turn') {
            throw ExtraccionCvException::respuestaIlegible();
        }

        foreach ($mensaje->content as $bloque) {
            if (! $bloque instanceof TextBlock) {
                continue;
            }

            $datos = json_decode($bloque->text, true);

            if (is_array($datos)) {
                return $datos;
            }
        }

        throw ExtraccionCvException::respuestaIlegible();
    }
}
