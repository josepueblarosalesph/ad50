<?php

namespace App\Services;

use App\Models\Postulante;
use App\Support\RecomendacionPerfil;

/**
 * Completitud del perfil del postulante y recomendaciones para subirla.
 *
 * El asistente de bienvenida solo exige el mínimo para aparecer en las búsquedas —datos
 * personales, titular, una experiencia y una formación— y deja saltar todo lo demás.
 * Quien lo recorre así termina con una ficha válida pero pobre y, hasta ahora, marcada
 * como 100 % completa: no había nada que le indicara qué le faltaba.
 *
 * Por eso la completitud se calcula **siempre desde los datos guardados**, nunca desde el
 * paso del asistente al que llegó la persona. Cada ítem aporta su peso solo si está
 * realmente lleno, y los que no lo están se devuelven como recomendaciones concretas
 * ordenadas por lo que más aportan.
 *
 * La columna `postulantes.completitud` es una copia persistida de este cálculo (la usa el
 * listado de admin para ordenar y filtrar); la fuente de verdad es este servicio.
 */
final class CompletitudPerfil
{
    /**
     * Cuántas recomendaciones caben en un aviso compacto (el de Oportunidades) antes de
     * que deje de leerse como un empujón y empiece a leerse como una tarea larga.
     */
    public const MAXIMO_EN_AVISO = 3;

    /**
     * Todos los ítems que componen el perfil, cumplidos y pendientes.
     *
     * Los pesos suman 100 y se reparten en dos bloques: 55 en lo obligatorio (lo que el
     * asistente exige, así que quien lo terminó parte de ahí) y 45 en lo opcional, que es
     * justamente lo que se puede saltar. El reparto dentro de cada bloque sigue el valor
     * que tiene el dato para la empresa que revisa la ficha.
     *
     * @return list<RecomendacionPerfil>
     */
    public static function items(?Postulante $postulante): array
    {
        return [
            new RecomendacionPerfil(
                clave: 'datos',
                seccion: 'datos',
                titulo: 'Completa tus datos personales',
                detalle: 'Documento, teléfono, año de nacimiento, género, nacionalidad, residencia y años de experiencia.',
                peso: 15,
                cumplida: self::datosCompletos($postulante),
                obligatoria: true,
            ),
            new RecomendacionPerfil(
                clave: 'titular',
                seccion: 'acerca',
                titulo: 'Escribe tu titular profesional',
                detalle: 'Es la primera línea que lee la empresa: resume en pocas palabras a qué te dedicas.',
                peso: 8,
                cumplida: filled($postulante?->titular),
                obligatoria: true,
            ),
            new RecomendacionPerfil(
                clave: 'experiencia',
                seccion: 'experiencia',
                titulo: 'Agrega tu experiencia laboral',
                detalle: 'Necesitas al menos un cargo para aparecer en las búsquedas de las empresas.',
                peso: 18,
                cumplida: self::tieneAlgunaExperiencia($postulante),
                obligatoria: true,
            ),
            new RecomendacionPerfil(
                clave: 'educacion',
                seccion: 'educacion',
                titulo: 'Agrega tu formación académica',
                detalle: 'Tus estudios permiten calzar con las búsquedas que piden una carrera o especialidad.',
                peso: 14,
                cumplida: self::tieneAlgunaEducacion($postulante),
                obligatoria: true,
            ),
            new RecomendacionPerfil(
                clave: 'resumen',
                seccion: 'acerca',
                titulo: 'Escribe tu presentación profesional',
                detalle: 'Dos o tres líneas sobre tu trayectoria y lo que aportas. Es lo que más se lee de una ficha.',
                peso: 8,
                cumplida: filled($postulante?->resumen_profesional),
            ),
            new RecomendacionPerfil(
                clave: 'habilidades',
                seccion: 'acerca',
                titulo: 'Selecciona tus habilidades',
                detalle: 'Hasta 6 habilidades que te describan. Las empresas también buscan por ellas.',
                peso: 6,
                cumplida: filled($postulante?->habilidades),
            ),
            new RecomendacionPerfil(
                clave: 'industrias',
                seccion: 'acerca',
                titulo: 'Indica tus industrias de interés',
                detalle: 'Acercan tu ficha a las búsquedas del rubro en que quieres seguir trabajando.',
                peso: 6,
                cumplida: filled($postulante?->industrias_interes),
            ),
            new RecomendacionPerfil(
                clave: 'curriculum',
                seccion: 'curriculum',
                titulo: 'Sube tu currículum en PDF',
                detalle: 'Complementa la ficha con el detalle de tu trayectoria para las empresas autorizadas.',
                peso: 6,
                cumplida: filled($postulante?->cv_ruta),
            ),
            new RecomendacionPerfil(
                clave: 'idiomas',
                seccion: 'idiomas',
                titulo: 'Agrega los idiomas que manejas',
                detalle: 'Indica cada idioma y tu nivel; varias ofertas se filtran por este dato.',
                peso: 4,
                cumplida: self::tieneAlgunIdioma($postulante),
            ),
            new RecomendacionPerfil(
                clave: 'regiones',
                seccion: 'acerca',
                titulo: 'Elige tus regiones de interés',
                detalle: 'Dónde estás dispuesto a trabajar, más allá de tu lugar de residencia.',
                peso: 4,
                cumplida: filled($postulante?->regiones_interes),
            ),
            new RecomendacionPerfil(
                clave: 'linkedin',
                seccion: 'datos',
                titulo: 'Enlaza tu perfil de LinkedIn',
                detalle: 'Da respaldo a tu trayectoria y es de lo primero que revisa un reclutador.',
                peso: 3,
                cumplida: filled($postulante?->linkedin),
            ),
            new RecomendacionPerfil(
                clave: 'modalidad',
                seccion: 'acerca',
                titulo: 'Indica tu modalidad de trabajo preferida',
                detalle: 'Jornada completa, parcial, honorarios: evita que te ofrezcan lo que no buscas.',
                peso: 3,
                cumplida: filled($postulante?->modalidad_trabajo),
            ),
            new RecomendacionPerfil(
                clave: 'situacion_laboral',
                seccion: 'acerca',
                titulo: 'Declara tu situación laboral actual',
                detalle: 'Le dice a la empresa qué tan pronto podrías incorporarte.',
                peso: 3,
                cumplida: filled($postulante?->situacion_laboral),
            ),
            new RecomendacionPerfil(
                clave: 'expectativa_renta',
                seccion: 'acerca',
                titulo: 'Declara tu expectativa de renta',
                detalle: 'Evita procesos que no calzan con lo que esperas. No se muestra en tu ficha pública.',
                peso: 2,
                cumplida: $postulante?->expectativa_renta !== null,
            ),
        ];
    }

    /** Porcentaje 0..100 de la ficha efectivamente llena. */
    public static function porcentaje(?Postulante $postulante): int
    {
        $items = self::items($postulante);

        $total = array_sum(array_map(fn (RecomendacionPerfil $item): int => $item->peso, $items));
        $logrado = array_sum(array_map(
            fn (RecomendacionPerfil $item): int => $item->cumplida ? $item->peso : 0,
            $items,
        ));

        return $total === 0 ? 0 : (int) round($logrado / $total * 100);
    }

    /**
     * Lo que falta, de mayor a menor aporte: primero lo que más sube el porcentaje.
     *
     * @return list<RecomendacionPerfil>
     */
    public static function pendientes(?Postulante $postulante, ?int $limite = null): array
    {
        $pendientes = array_values(array_filter(
            self::items($postulante),
            fn (RecomendacionPerfil $item): bool => ! $item->cumplida,
        ));

        // usort es estable en PHP 8, así que a igual peso manda el orden de declaración.
        usort($pendientes, fn (RecomendacionPerfil $a, RecomendacionPerfil $b): int => $b->peso <=> $a->peso);

        return $limite === null ? $pendientes : array_slice($pendientes, 0, $limite);
    }

    private static function datosCompletos(?Postulante $postulante): bool
    {
        if ($postulante === null) {
            return false;
        }

        // blank() no descarta el 0, así que "0 años de experiencia" cuenta como declarado.
        $obligatorios = ['rut', 'telefono', 'anio_nacimiento', 'genero', 'nacionalidad', 'ciudad', 'anios_experiencia'];

        foreach ($obligatorios as $campo) {
            if (blank($postulante->{$campo})) {
                return false;
            }
        }

        return true;
    }

    private static function tieneAlgunaExperiencia(?Postulante $postulante): bool
    {
        if ($postulante === null) {
            return false;
        }

        return collect($postulante->experiencias)
            ->contains(fn (mixed $experiencia): bool => is_array($experiencia) && filled($experiencia['cargo'] ?? null));
    }

    private static function tieneAlgunaEducacion(?Postulante $postulante): bool
    {
        if ($postulante === null) {
            return false;
        }

        return collect($postulante->educaciones)
            ->contains(fn (mixed $educacion): bool => is_array($educacion) && filled($educacion['institucion'] ?? null));
    }

    /** El formulario conserva una fila vacía para poder seguir agregando: no cuenta. */
    private static function tieneAlgunIdioma(?Postulante $postulante): bool
    {
        if ($postulante === null) {
            return false;
        }

        return collect($postulante->idiomas)
            ->contains(fn (mixed $idioma): bool => is_array($idioma) && filled($idioma['idioma'] ?? null));
    }
}
