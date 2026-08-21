<?php

namespace App\Support;

use App\Rules\RutValido;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Traduce la estructura que devuelve el lector de CV a los campos de la ficha.
 *
 * Todo lo que sale de aquí tiene que pasar la validación de [\App\Livewire\Postulante\Ficha]
 * sin retoques: los valores de lista cerrada se verifican contra el catálogo y los de
 * texto libre se recortan a la longitud que acepta el formulario. Lo que no calza se
 * descarta —queda vacío para que lo complete la persona— en vez de aproximarse: un
 * cargo equivocado envenena el matching, y un campo en blanco no.
 *
 * Los catálogos grandes (cargos, empresas) no viajan en el esquema del modelo, así que
 * el calce se hace aquí por texto normalizado. Si no hay calce exacto se usa la salida
 * que la ficha ya contempla: "Otros" / "Otra" más el texto original.
 */
class FichaDesdeCv
{
    /** La ficha admite hasta 5 experiencias, 20 educaciones y 6 habilidades. */
    private const MAX_EXPERIENCIAS = 5;

    private const MAX_EDUCACIONES = 20;

    private const MAX_HABILIDADES = 6;

    private const MAX_INDUSTRIAS_INTERES = 6;

    private const MAX_REGIONES_INTERES = 5;

    /**
     * Índices normalizados de catálogo, mientras dura un mapeo: normalizar 30.000 cargos
     * no es gratis y una extracción los consulta varias veces. Se descartan al terminar
     * (ver el final de `mapear()`), porque ocupan unos 6 MB.
     *
     * @var array<string, array<string, string>>
     */
    private static array $indices = [];

    /**
     * @param  array<string, mixed>  $cv  Salida del lector, ya saneada.
     * @return array<string, mixed> Campos con los nombres que usa el componente Livewire.
     */
    public static function mapear(array $cv): array
    {
        $persona = self::arreglo($cv['persona'] ?? null);

        $datos = [
            ...self::datosPersonales($persona),
            'titular' => self::texto($persona['titular'] ?? null, 100),
            'resumenProfesional' => self::texto($persona['resumen_profesional'] ?? null, 900),
            'situacionLaboral' => self::deCatalogo($persona['situacion_laboral'] ?? null, CatalogosProfesionales::situacionesLaborales()),
            'expectativaRenta' => self::entero($persona['expectativa_renta'] ?? null, 0, 100000000),
            'habilidades' => self::habilidades($cv['habilidades'] ?? null),
            'industriasInteres' => self::seleccion($cv['industrias_interes'] ?? null, CatalogosProfesionales::industrias(), self::MAX_INDUSTRIAS_INTERES),
            'regionesInteres' => self::seleccion($cv['regiones_interes'] ?? null, CatalogosProfesionales::regionesInteres(), self::MAX_REGIONES_INTERES),
            'experiencias' => self::experiencias($cv['experiencia'] ?? null),
            'educaciones' => self::educaciones($cv['educacion'] ?? null),
            'idiomas' => self::idiomas($cv['idiomas'] ?? null),
        ];

        // Los índices normalizados de cargos y empresas ocupan unos 6 MB y ya no hacen
        // falta: se sueltan aquí en vez de quedar colgando del proceso, que en un worker
        // atiende un CV tras otro.
        self::$indices = [];

        return $datos;
    }

    /**
     * @param  array<string, mixed>  $persona
     * @return array<string, mixed>
     */
    private static function datosPersonales(array $persona): array
    {
        $datos = [
            'nombres' => self::texto($persona['nombres'] ?? null, 50),
            'apellidos' => self::texto($persona['apellidos'] ?? null, 50),
            'email' => self::email($persona['email'] ?? null),
            'telefono' => self::telefono($persona['telefono'] ?? null),
            'linkedin' => self::url($persona['linkedin'] ?? null),
            'sitioWeb' => self::url($persona['sitio_web'] ?? null),
            'anioNacimiento' => self::entero($persona['anio_nacimiento'] ?? null, 1900, (int) now()->year),
            'aniosExperiencia' => self::entero($persona['anios_experiencia'] ?? null, 0, 80),
            'genero' => self::deCatalogo($persona['genero'] ?? null, CatalogosProfesionales::generos()),
            'nacionalidad' => self::deCatalogo($persona['nacionalidad'] ?? null, CatalogosProfesionales::nacionalidades()),
            // La ficha guarda la región de residencia en el campo `ciudad`.
            'ciudad' => self::deCatalogo($persona['region'] ?? null, CatalogosProfesionales::regiones()),
        ];

        // Solo se rellena el RUT si además pasa el dígito verificador: uno mal leído
        // bloquearía el guardado con un error que la persona no sabría de dónde sale.
        $rut = self::texto($persona['rut'] ?? null, 20);

        if ($rut !== null && Validator::make(['rut' => $rut], ['rut' => new RutValido])->passes()) {
            $datos['rut'] = Rut::formatear($rut);
            $datos['tipoDocumento'] = 'rut';
        }

        return $datos;
    }

    /**
     * @param  mixed  $experiencias
     * @return array<int, array<string, mixed>>
     */
    private static function experiencias($experiencias): array
    {
        $salida = [];

        foreach (self::filas($experiencias, self::MAX_EXPERIENCIAS) as $fila) {
            $cargo = self::texto($fila['cargo'] ?? null, 120);

            // Sin cargo la fila no sirve para nada: es el campo del que cuelga el matching.
            if ($cargo === null) {
                continue;
            }

            $cargoCatalogo = self::calzar($cargo, CatalogosProfesionales::cargos(), 'cargos');
            $empresa = self::texto($fila['empresa'] ?? null, 160);
            $empresaCatalogo = $empresa === null ? null : self::calzar($empresa, CatalogosProfesionales::empresas(), 'empresas');
            $actual = (bool) ($fila['es_actual'] ?? false);
            $inicioAnio = self::entero($fila['inicio_anio'] ?? null, 1950, (int) now()->year);
            $finAnio = $actual ? null : self::entero($fila['fin_anio'] ?? null, 1950, (int) now()->year);

            $salida[] = [
                'cargo' => $cargoCatalogo ?? 'Otros',
                'cargo_otro' => $cargoCatalogo === null ? $cargo : '',
                'tipo_trabajo' => self::deCatalogo($fila['tipo_trabajo'] ?? null, CatalogosProfesionales::tiposTrabajo()) ?? 'Jornada completa',
                'empresa' => $empresaCatalogo ?? 'Otra',
                'empresa_otro' => $empresaCatalogo === null ? ($empresa ?? '') : '',
                'jerarquia' => self::deCatalogo($fila['jerarquia'] ?? null, CatalogosProfesionales::jerarquias()) ?? '',
                'actividad_empresa' => self::deCatalogo($fila['industria'] ?? null, CatalogosProfesionales::industrias()) ?? '',
                // El mes es obligatorio en el formulario y el CV muchas veces solo trae el
                // año. Se propone enero (o diciembre para el término) y la persona corrige.
                'inicio_mes' => $inicioAnio === null ? null : (self::entero($fila['inicio_mes'] ?? null, 1, 12) ?? 1),
                'inicio_anio' => $inicioAnio,
                'actualmente' => $actual,
                'fin_mes' => $finAnio === null ? null : (self::entero($fila['fin_mes'] ?? null, 1, 12) ?? 12),
                'fin_anio' => $finAnio,
                'responsabilidades' => self::texto($fila['responsabilidades'] ?? null, 3000) ?? '',
            ];
        }

        return $salida;
    }

    /**
     * @param  mixed  $educaciones
     * @return array<int, array<string, mixed>>
     */
    private static function educaciones($educaciones): array
    {
        $salida = [];

        foreach (self::filas($educaciones, self::MAX_EDUCACIONES) as $fila) {
            $institucion = self::texto($fila['institucion'] ?? null, 180);
            $nivel = self::deCatalogo($fila['nivel'] ?? null, CatalogosProfesionales::nivelesEstudio());

            // Institución y nivel son obligatorios; sin ellos la fila solo estorbaría.
            if ($institucion === null || $nivel === null) {
                continue;
            }

            $carrera = self::texto($fila['carrera'] ?? null, 180);
            $situacion = self::deCatalogo($fila['situacion'] ?? null, CatalogosProfesionales::situacionesEstudio());

            $salida[] = [
                'nivel' => $nivel,
                // El formulario ofrece el país en un <select> del catálogo, así que un
                // texto que no calce no quedaría seleccionado: mejor caer a Chile.
                'pais' => self::calzarTexto($fila['pais'] ?? null, CatalogosProfesionales::paises(), 'paises') ?? 'Chile',
                'institucion' => self::calzar($institucion, CatalogosProfesionales::instituciones(), 'instituciones') ?? $institucion,
                'carrera' => $carrera === null ? null : (self::calzar($carrera, CatalogosProfesionales::carrerasEstudio(), 'carreras') ?? $carrera),
                'mencion' => self::texto($fila['mencion'] ?? null, 180),
                'modalidad' => self::deCatalogo($fila['modalidad'] ?? null, CatalogosProfesionales::modalidadesEstudio()),
                'situacion' => $situacion,
                'inicio_anio' => self::entero($fila['inicio_anio'] ?? null, 1900, (int) now()->year),
                // Mientras estudia no hay año de término: el formulario lo exige vacío.
                'termino_anio' => $situacion === 'Estudiando' ? null : self::entero($fila['termino_anio'] ?? null, 1900, (int) now()->year),
                'egreso_anio' => null,
            ];
        }

        return $salida;
    }

    /**
     * @param  mixed  $idiomas
     * @return array<int, array{idioma: string, nivel: string}>
     */
    private static function idiomas($idiomas): array
    {
        $salida = [];
        $vistos = [];

        foreach (self::filas($idiomas, count(CatalogosProfesionales::idiomas())) as $fila) {
            $idioma = self::deCatalogo($fila['idioma'] ?? null, CatalogosProfesionales::idiomas());
            $nivel = self::deCatalogo($fila['nivel'] ?? null, CatalogosProfesionales::nivelesIdioma());

            // Ambos campos son obligatorios y el idioma no se puede repetir.
            if ($idioma === null || $nivel === null || in_array($idioma, $vistos, true)) {
                continue;
            }

            $vistos[] = $idioma;
            $salida[] = ['idioma' => $idioma, 'nivel' => $nivel];
        }

        return $salida;
    }

    /**
     * Solo habilidades que existan tal cual en el catálogo: el formulario las valida
     * con Rule::in y una inventada rompería el guardado.
     *
     * @param  mixed  $habilidades
     * @return array<int, string>
     */
    private static function habilidades($habilidades): array
    {
        $salida = [];

        foreach (self::filas($habilidades, 60) as $habilidad) {
            $texto = self::texto(is_string($habilidad) ? $habilidad : null, 120);
            $calce = $texto === null ? null : self::calzar($texto, CatalogosProfesionales::habilidades(), 'habilidades');

            if ($calce !== null && ! in_array($calce, $salida, true)) {
                $salida[] = $calce;
            }

            if (count($salida) === self::MAX_HABILIDADES) {
                break;
            }
        }

        return $salida;
    }

    /**
     * @param  mixed  $valores
     * @param  array<int, string>  $catalogo
     * @return array<int, string>
     */
    private static function seleccion($valores, array $catalogo, int $maximo): array
    {
        $salida = [];

        foreach (self::filas($valores, $maximo * 3) as $valor) {
            $calce = self::deCatalogo(is_string($valor) ? $valor : null, $catalogo);

            if ($calce !== null && ! in_array($calce, $salida, true)) {
                $salida[] = $calce;
            }

            if (count($salida) === $maximo) {
                break;
            }
        }

        return $salida;
    }

    /**
     * Filas de una lista, acotadas al máximo que acepta el formulario.
     *
     * @param  mixed  $valor
     * @return array<int, mixed>
     */
    private static function filas($valor, int $maximo): array
    {
        return is_array($valor) ? array_slice(array_values($valor), 0, $maximo) : [];
    }

    /**
     * @param  mixed  $valor
     * @return array<string, mixed>
     */
    private static function arreglo($valor): array
    {
        return is_array($valor) ? $valor : [];
    }

    /** @param mixed $valor */
    private static function texto($valor, int $maximo): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $texto = trim(preg_replace('/[ \t]+/u', ' ', $valor) ?? '');

        return $texto === '' ? null : mb_substr($texto, 0, $maximo);
    }

    /** @param mixed $valor */
    private static function entero($valor, int $minimo, int $maximo): ?int
    {
        if (! is_int($valor) && ! (is_string($valor) && ctype_digit($valor))) {
            return null;
        }

        $numero = (int) $valor;

        return $numero >= $minimo && $numero <= $maximo ? $numero : null;
    }

    /** @param mixed $valor */
    private static function email($valor): ?string
    {
        $email = self::texto($valor, 255);

        return $email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    /**
     * El formulario pide solo dígitos y separadores; se descarta cualquier otra cosa.
     *
     * @param  mixed  $valor
     */
    private static function telefono($valor): ?string
    {
        $telefono = self::texto($valor, 30);

        return $telefono !== null && preg_match('/^[0-9+\-() ]+$/', $telefono) === 1 ? $telefono : null;
    }

    /**
     * Los CV suelen escribir el enlace sin esquema ("linkedin.com/in/…"); se completa
     * con https porque la ficha valida `url:http,https`.
     *
     * @param  mixed  $valor
     */
    private static function url($valor): ?string
    {
        $url = self::texto($valor, 100);

        if ($url === null) {
            return null;
        }

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            $url = 'https://'.$url;
        }

        return mb_strlen($url) <= 100 && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
    }

    /**
     * Calce contra el catálogo a partir de texto libre del modelo.
     *
     * @param  mixed  $valor
     * @param  array<int, string>  $catalogo
     */
    private static function calzarTexto($valor, array $catalogo, string $clave): ?string
    {
        $texto = self::texto($valor, 100);

        return $texto === null ? null : self::calzar($texto, $catalogo, $clave);
    }

    /**
     * @param  mixed  $valor
     * @param  array<int, string>  $catalogo
     */
    private static function deCatalogo($valor, array $catalogo): ?string
    {
        return is_string($valor) && in_array($valor, $catalogo, true) ? $valor : null;
    }

    /**
     * Calce por texto normalizado (sin tildes, ni mayúsculas, ni puntuación).
     *
     * A propósito no hay calce difuso: con 30.000 cargos, un "parecido" acierta poco y
     * cuando falla mete al postulante en una búsqueda que no le corresponde.
     *
     * @param  array<int, string>  $catalogo
     * @param  string  $clave  Identificador del catálogo para reutilizar su índice.
     */
    private static function calzar(string $texto, array $catalogo, string $clave): ?string
    {
        $indice = self::$indices[$clave] ??= self::indexar($catalogo);

        return $indice[self::normalizar($texto)] ?? null;
    }

    /**
     * @param  array<int, string>  $catalogo
     * @return array<string, string>
     */
    private static function indexar(array $catalogo): array
    {
        $indice = [];

        foreach ($catalogo as $valor) {
            $indice[self::normalizar($valor)] ??= $valor;
        }

        return $indice;
    }

    private static function normalizar(string $texto): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        $texto = mb_strtolower($ascii === false ? $texto : $ascii, 'UTF-8');

        return trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^a-z0-9]+/', ' ', $texto)));
    }
}
