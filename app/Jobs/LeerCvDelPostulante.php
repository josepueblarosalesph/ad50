<?php

namespace App\Jobs;

use App\Services\ExtraccionCvException;
use App\Services\ExtractorCv;
use App\Support\EstadoLecturaCv;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Lee el CV en segundo plano y deja el resultado en [EstadoLecturaCv].
 *
 * La lectura no cabe en una petición web: medida contra la API real, un CV de una
 * página tarda del orden de 80 segundos, y el servidor corta bastante antes (nginx
 * usa 60 s por defecto). Hacerlo aquí es lo que evita el 502 y, de paso, deja de
 * bloquear la pantalla mientras la persona espera.
 */
class LeerCvDelPostulante implements ShouldQueue
{
    use Queueable;

    /** Holgado a propósito: un CV largo puede tardar varios minutos. */
    public int $timeout = 300;

    /**
     * Un solo intento: cada reintento gasta cuota del proveedor y la persona ya tiene
     * el botón para volver a probar cuando quiera.
     */
    public int $tries = 1;

    public function __construct(
        public int $postulanteId,
        public string $rutaCv,
    ) {}

    public function handle(ExtractorCv $extractor): void
    {
        try {
            $pdf = Storage::disk('local')->get($this->rutaCv);

            if ($pdf === null) {
                EstadoLecturaCv::guardarError($this->postulanteId, ExtraccionCvException::respuestaIlegible()->getMessage());

                return;
            }

            EstadoLecturaCv::guardarResultado($this->postulanteId, $extractor->extraer($pdf, $this->postulanteId));
        } catch (ExtraccionCvException $e) {
            // Motivo entendible (archivo rechazado, cuota, servicio caído): se le muestra.
            EstadoLecturaCv::guardarError($this->postulanteId, $e->getMessage());
        }
    }

    /**
     * Cualquier otra falla: la persona ve un mensaje genérico y el detalle queda en el log.
     */
    public function failed(?Throwable $e): void
    {
        EstadoLecturaCv::guardarError($this->postulanteId, ExtraccionCvException::servicioNoDisponible()->getMessage());
    }
}
