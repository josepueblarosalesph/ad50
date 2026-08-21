<?php

namespace App\Services;

use App\Support\EsquemaCv;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Lee el CV con Gemini a través de la Interactions API de Google AI Studio.
 *
 * Se llama por HTTP y no por un SDK: Google no publica uno oficial para PHP, y la
 * Interactions API es una sola petición JSON que encaja con el `Http::` que ya usa el
 * resto del proyecto (ver [FlowService]).
 *
 * Mismas instrucciones y mismo esquema que el lector de Claude ([EsquemaCv]): Gemini
 * acepta el subconjunto de JSON Schema que usamos (`anyOf`, `enum`, `required`,
 * `additionalProperties`), así que la respuesta llega con la forma que espera
 * [\App\Support\FichaDesdeCv] y el extractor no necesita saber quién leyó el documento.
 *
 * Ojo con la forma de la respuesta: la Interactions API no devuelve `candidates` como
 * el antiguo `generateContent`, sino una lista `steps` con el detalle del turno; el
 * texto está en el último paso de tipo `model_output`.
 */
class LectorDeCvGemini implements LectorDeCv
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    /** Un PDF puede tardar; el tope de subida de la ficha son 10 MB. */
    private const TIMEOUT_SEGUNDOS = 120;

    public function disponible(): bool
    {
        return filled(config('services.gemini.api_key'));
    }

    public function nombre(): string
    {
        return 'gemini';
    }

    public function modelo(): string
    {
        return (string) config('services.gemini.modelo');
    }

    public function leer(string $pdf): array
    {
        if (! $this->disponible()) {
            throw ExtraccionCvException::servicioNoDisponible();
        }

        try {
            $respuesta = Http::withHeaders(['x-goog-api-key' => (string) config('services.gemini.api_key')])
                ->timeout(self::TIMEOUT_SEGUNDOS)
                ->post(self::ENDPOINT, [
                    'model' => $this->modelo(),
                    'system_instruction' => EsquemaCv::INSTRUCCIONES,
                    'input' => [
                        ['type' => 'document', 'mime_type' => 'application/pdf', 'data' => base64_encode($pdf)],
                        ['type' => 'text', 'text' => EsquemaCv::PETICION],
                    ],
                    'response_format' => [
                        'type' => 'text',
                        'mime_type' => 'application/json',
                        'schema' => EsquemaCv::esquema(),
                    ],
                    // Transcribir y estructurar no necesita razonamiento profundo, y es
                    // la palanca de costo: subirlo encarece cada CV leído.
                    'generation_config' => ['thinking_level' => 'low'],
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            report($e);

            // 429 no es una falla: es la cuota del proveedor. Distinguirlo evita que la
            // persona crea que algo se rompió, y que quien mire el log busque un bug.
            throw $e->response->status() === 429
                ? ExtraccionCvException::cuotaAgotada()
                : ExtraccionCvException::servicioNoDisponible();
        }

        $datos = json_decode($this->textoDeLaRespuesta($respuesta), true);

        if (! is_array($datos)) {
            throw ExtraccionCvException::respuestaIlegible();
        }

        return $datos;
    }

    /**
     * Junta el texto del último paso en que habló el modelo.
     *
     * Se recorre al revés porque `steps` es la línea de tiempo completa del turno: antes
     * del texto final puede haber pasos de otro tipo, y el que interesa es el último.
     *
     * @param  mixed  $respuesta
     */
    private function textoDeLaRespuesta($respuesta): string
    {
        $pasos = is_array($respuesta) ? ($respuesta['steps'] ?? []) : [];

        if (! is_array($pasos)) {
            return '';
        }

        foreach (array_reverse($pasos) as $paso) {
            if (! is_array($paso) || ($paso['type'] ?? null) !== 'model_output') {
                continue;
            }

            $texto = '';

            foreach ($paso['content'] ?? [] as $bloque) {
                if (is_array($bloque) && ($bloque['type'] ?? null) === 'text' && is_string($bloque['text'] ?? null)) {
                    $texto .= $bloque['text'];
                }
            }

            if ($texto !== '') {
                return $texto;
            }
        }

        return '';
    }
}
