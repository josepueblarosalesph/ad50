<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * El estado de la lectura de un CV mientras ocurre en segundo plano.
 *
 * Vive en caché y no en la base porque es información de un rato: existe entre que la
 * persona sube el archivo y ve los campos propuestos, y después no le sirve a nadie. Un
 * postulante tiene a lo más una lectura en curso, así que la clave es su id.
 */
class EstadoLecturaCv
{
    /** Suficiente para que alcance a terminar y para que la persona vuelva de un café. */
    private const MINUTOS = 30;

    public static function marcarEnCurso(int $postulanteId): void
    {
        self::guardar($postulanteId, ['estado' => 'en_curso']);
    }

    public static function guardarResultado(int $postulanteId, ResultadoCv $resultado): void
    {
        self::guardar($postulanteId, [
            'estado' => 'listo',
            'datos' => $resultado->datos,
            'confianza' => $resultado->confianza,
            'flags' => $resultado->flags,
            'notas' => $resultado->notas,
        ]);
    }

    public static function guardarError(int $postulanteId, string $mensaje): void
    {
        self::guardar($postulanteId, ['estado' => 'error', 'mensaje' => $mensaje]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function leer(int $postulanteId): ?array
    {
        $estado = Cache::get(self::clave($postulanteId));

        return is_array($estado) ? $estado : null;
    }

    public static function olvidar(int $postulanteId): void
    {
        Cache::forget(self::clave($postulanteId));
    }

    /**
     * @param  array<string, mixed>  $estado
     */
    private static function guardar(int $postulanteId, array $estado): void
    {
        Cache::put(self::clave($postulanteId), $estado, now()->addMinutes(self::MINUTOS));
    }

    private static function clave(int $postulanteId): string
    {
        return "lectura_cv:$postulanteId";
    }
}
