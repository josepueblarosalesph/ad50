<?php

namespace App\Services;

use App\Support\EsquemaCv;
use App\Support\FichaDesdeCv;
use App\Support\ResultadoCv;
use Illuminate\Support\Facades\Log;
use Normalizer;

/**
 * Convierte el CV en PDF que sube el postulante en campos de su ficha profesional.
 *
 * El modelo es la última barrera, no la primera. El orden es:
 *
 *  1. Validación del archivo: tipo real por magic bytes, tamaño, páginas y rechazo de
 *     PDF con contenido activo.
 *  2. Lectura con el [LectorDeCv] configurado (Claude o Gemini), que manda el PDF a un
 *     modelo sin herramientas, sin historial y sin ningún dato de la plataforma ni de
 *     otros postulantes.
 *  3. Saneamiento de la salida: normalización Unicode, barrido de patrones de
 *     inyección y rechazo de valores con HTML, marcadores de rol o URL inesperadas.
 *  4. Mapeo a los catálogos de la ficha ([FichaDesdeCv]).
 *  5. Registro de auditoría, sin contenido del documento.
 *
 * Cerrando la cadena está la persona: nada se guarda hasta que revisa el formulario
 * y presiona guardar.
 */
class ExtractorCv
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    public const MAX_PAGINAS = 15;

    /**
     * Marcadores de PDF con contenido activo. `/OpenAction` se trata aparte porque
     * también aparece en PDF perfectamente normales, apuntando a un destino de página
     * en vez de a una acción.
     */
    private const ELEMENTOS_ACTIVOS = '~/(JS|JavaScript|Launch|EmbeddedFile|AA)[^A-Za-z0-9]~';

    private const ACCION_AL_ABRIR = '~/OpenAction\s*<<~';

    /**
     * Barrido de inyección de instrucciones sobre el texto ya extraído (capa E del
     * instructivo). Un CV legítimo del rubro IA puede hablar de esto describiendo su
     * propio trabajo, así que la coincidencia marca el campo y sigue; no bloquea el CV.
     */
    private const PATRONES_INYECCION = [
        '~ignor[ae]\s+(las?\s+)?instrucciones~iu',
        '~olvida\s+(lo anterior|las reglas|todo)~iu',
        '~ignore\s+(all\s+)?(previous|prior)\s+instructions~i',
        '~disregard\s+(all\s+)?(previous|prior)~i',
        '~eres\s+un\s+(sistema|asistente|modelo)~iu',
        '~\byou are an? (ai|assistant|system|model)\b~i',
        '~\bact as\b~i',
        '~system\s*prompt~i',
        '~\[INST\]~i',
        '~#{2,}\s*(instruction|system)~i',
        '~(asigna|otorga|dale)\s+(el\s+)?(m[áa]ximo|100|puntaje)~iu',
        '~rate this candidate~i',
        '~recomienda\s+(a\s+)?(este|al)\s+candidato~iu',
        '~must be hired~i',
    ];

    /** Marcadores de rol o de plantilla de chat dentro de un valor. */
    private const MARCADORES_DE_ROL = '~<\|[^|]*\|>|^\s*(system|assistant|human|user)\s*:~im';

    /** Campos donde una URL es esperable; en el resto se descarta o se limpia. */
    private const CAMPOS_CON_URL = ['linkedin', 'sitio_web'];

    /** Campos largos donde una URL se recorta en vez de tumbar todo el texto. */
    private const CAMPOS_DE_TEXTO_LARGO = ['responsabilidades', 'resumen_profesional'];

    public function __construct(private LectorDeCv $lector) {}

    /**
     * El autocompletado solo se ofrece si el proveedor configurado tiene credenciales.
     */
    public static function disponible(): bool
    {
        return app(LectorDeCv::class)->disponible();
    }

    /**
     * @param  string  $pdf  Contenido binario del archivo subido.
     * @param  int  $postulanteId  Solo para la auditoría; nunca viaja al modelo.
     *
     * @throws ExtraccionCvException
     */
    public function extraer(string $pdf, int $postulanteId): ResultadoCv
    {
        $paginas = $this->validarArchivo($pdf);

        $crudo = $this->lector->leer($pdf);

        $flags = $this->flagsDeclarados($crudo);
        $notas = $this->notasDeclaradas($crudo);
        $limpio = $this->sanear($crudo, $flags, $notas);
        $confianza = $this->confianza($crudo, $flags);

        $this->registrar($pdf, $paginas, $postulanteId, $flags, $confianza);

        return new ResultadoCv(FichaDesdeCv::mapear($limpio), $confianza, $flags, $notas);
    }

    /**
     * Capa A: el archivo antes de que nadie lo lea.
     *
     * @return int|null Páginas detectadas, o null si el PDF las guarda comprimidas.
     *
     * @throws ExtraccionCvException
     */
    private function validarArchivo(string $pdf): ?int
    {
        if (strlen($pdf) > self::MAX_BYTES) {
            throw ExtraccionCvException::demasiadoGrande();
        }

        // El tipo real, no la extensión ni el Content-Type que mandó el navegador.
        if (! str_starts_with($pdf, '%PDF-')) {
            throw ExtraccionCvException::archivoNoEsPdf();
        }

        if (preg_match(self::ELEMENTOS_ACTIVOS, $pdf) === 1 || preg_match(self::ACCION_AL_ABRIR, $pdf) === 1) {
            throw ExtraccionCvException::contieneElementosActivos();
        }

        // Un PDF con object streams esconde los /Type /Page: ahí el conteo da cero y se
        // deja pasar, porque el tope de páginas es una salvaguarda de costo, no de
        // seguridad, y el tamaño máximo ya acota el caso.
        $paginas = preg_match_all('~/Type\s*/Page[^s]~', $pdf);

        if ($paginas > self::MAX_PAGINAS) {
            throw ExtraccionCvException::demasiadasPaginas(self::MAX_PAGINAS);
        }

        return $paginas > 0 ? $paginas : null;
    }

    /**
     * Capa F: recorre la salida del modelo y descarta lo que no debería estar ahí.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, string>  $flags
     * @param  array<int, string>  $notas
     * @return array<string, mixed>
     */
    private function sanear(array $datos, array &$flags, array &$notas): array
    {
        foreach ($datos as $clave => $valor) {
            if (is_array($valor)) {
                $datos[$clave] = $this->sanear($valor, $flags, $notas);

                continue;
            }

            if (! is_string($valor)) {
                continue;
            }

            $datos[$clave] = $this->sanearTexto((string) $clave, $valor, $flags, $notas);
        }

        return $datos;
    }

    /**
     * @param  array<int, string>  $flags
     * @param  array<int, string>  $notas
     */
    private function sanearTexto(string $clave, string $valor, array &$flags, array &$notas): ?string
    {
        $texto = $this->normalizarUnicode($valor, $flags);

        foreach (self::PATRONES_INYECCION as $patron) {
            if (preg_match($patron, $texto) === 1) {
                $this->agregar($flags, 'instruccion_en_el_documento');
                $this->agregar($notas, "Descartamos el campo «{$clave}» porque su contenido parecía una instrucción dirigida al sistema.");

                return null;
            }
        }

        // Etiquetas HTML/XML y marcadores de plantilla de chat: nada de eso pertenece a
        // un dato de la ficha, y su sola presencia delata un documento manipulado.
        if (preg_match('~<[a-z!/][^>]*>~i', $texto) === 1 || preg_match(self::MARCADORES_DE_ROL, $texto) === 1) {
            $this->agregar($flags, 'marcadores_de_rol_o_html');

            return null;
        }

        if (preg_match('~https?://~i', $texto) === 1 && ! in_array($clave, self::CAMPOS_CON_URL, true)) {
            if (! in_array($clave, self::CAMPOS_DE_TEXTO_LARGO, true)) {
                return null;
            }

            // En un texto largo la URL puede ser un proyecto propio: se quita el enlace
            // y se conserva el resto en vez de perder toda la descripción.
            $texto = trim((string) preg_replace('~https?://\S+~i', '', $texto));
        }

        return $texto === '' ? null : $texto;
    }

    /**
     * Capa D: deja el texto en una sola forma canónica y sin caracteres invisibles.
     *
     * @param  array<int, string>  $flags
     */
    private function normalizarUnicode(string $texto, array &$flags): string
    {
        if (class_exists(Normalizer::class)) {
            $texto = Normalizer::normalize($texto, Normalizer::FORM_KC) ?: $texto;
        }

        $limpio = (string) preg_replace(
            [
                '~[\x{200B}-\x{200D}\x{2060}\x{FEFF}]~u',   // ancho cero
                '~[\x{E0000}-\x{E007F}]~u',                  // bloque de tags (texto invisible)
                '~[\x{202A}-\x{202E}\x{2066}-\x{2069}]~u',   // controles bidi
                '~[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]~u', // control C0/C1
            ],
            '',
            $texto,
        );

        if ($limpio !== $texto) {
            $this->agregar($flags, 'caracteres_invisibles');
        }

        return trim((string) preg_replace('~\n{3,}~', "\n\n", $limpio));
    }

    /**
     * @param  array<string, mixed>  $crudo
     * @return array<int, string>
     */
    private function flagsDeclarados(array $crudo): array
    {
        // El detalle de lo que vio el lector no se guarda: viene del documento y
        // mostrarlo sería reinyectar el texto sospechoso en la pantalla.
        return $this->listaDeMeta($crudo, 'flags_seguridad') === [] ? [] : ['instruccion_en_el_documento'];
    }

    /**
     * Notas del propio lector (fechas ilegibles, campos ambiguos), acotadas para no
     * llenar la pantalla.
     *
     * @param  array<string, mixed>  $crudo
     * @return array<int, string>
     */
    private function notasDeclaradas(array $crudo): array
    {
        return array_slice($this->listaDeMeta($crudo, 'notas_extraccion'), 0, 5);
    }

    /**
     * @param  array<string, mixed>  $crudo
     * @param  array<int, string>  $flags
     * @return 'alta'|'media'|'baja'
     */
    private function confianza(array $crudo, array $flags): string
    {
        if ($flags !== []) {
            return 'baja';
        }

        $declarada = data_get($crudo, 'meta.confianza');

        return in_array($declarada, ['alta', 'media', 'baja'], true) ? $declarada : 'media';
    }

    /**
     * @param  array<string, mixed>  $crudo
     * @return array<int, string>
     */
    private function listaDeMeta(array $crudo, string $clave): array
    {
        $valores = data_get($crudo, "meta.$clave");

        if (! is_array($valores)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $v): string => is_string($v) ? trim($v) : '', $valores),
            fn (string $v): bool => $v !== '',
        ));
    }

    /**
     * Punto 7 del instructivo: queda registro de qué se procesó, nunca de qué decía.
     *
     * @param  array<int, string>  $flags
     */
    private function registrar(string $pdf, ?int $paginas, int $postulanteId, array $flags, string $confianza): void
    {
        Log::info('extraccion_cv', [
            'postulante_id' => $postulanteId,
            'hash' => hash('sha256', $pdf),
            'bytes' => strlen($pdf),
            'paginas' => $paginas,
            'flags' => $flags,
            'confianza' => $confianza,
            'proveedor' => $this->lector->nombre(),
            'modelo' => $this->lector->modelo(),
            'version_prompt' => EsquemaCv::VERSION,
        ]);
    }

    /**
     * @param  array<int, string>  $lista
     */
    private function agregar(array &$lista, string $valor): void
    {
        if (! in_array($valor, $lista, true)) {
            $lista[] = $valor;
        }
    }
}
