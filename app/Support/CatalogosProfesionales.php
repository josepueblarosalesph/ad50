<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogosProfesionales
{
    private const CACHE_PREFIJO = 'catalogo_terminos:';

    /**
     * Valores vigentes de un catálogo administrable.
     *
     * Se leen de `terminos_catalogo` y se cachean. Si la tabla no existe todavía (una
     * instalación a medio migrar) o el catálogo está vacío, se devuelven los valores
     * por defecto del código: la plataforma nunca se queda sin opciones.
     *
     * @return list<string>
     */
    private static function desdeCatalogo(string $catalogo, string $origen): array
    {
        $terminos = Cache::remember(
            self::CACHE_PREFIJO.$catalogo,
            now()->addHour(),
            function () use ($catalogo): array {
                if (! Schema::hasTable('terminos_catalogo')) {
                    return [];
                }

                return DB::table('terminos_catalogo')
                    ->where('catalogo', $catalogo)
                    ->orderBy('orden')
                    ->orderBy('valor')
                    ->pluck('valor')
                    ->all();
            },
        );

        return $terminos === [] ? self::porDefecto($origen) : array_values($terminos);
    }

    /**
     * Valores originales de un catálogo, tal como están escritos en el código. Es la
     * fuente de la carga inicial y el respaldo cuando la tabla está vacía.
     *
     * @return list<string>
     */
    public static function porDefecto(string $origen): array
    {
        $metodo = $origen.'PorDefecto';

        return method_exists(self::class, $metodo) ? array_values(self::$metodo()) : [];
    }

    /** Descarta la caché de un catálogo, o de todos si no se indica ninguno. */
    public static function olvidar(?string $catalogo = null): void
    {
        foreach ($catalogo !== null ? [$catalogo] : CatalogosAdministrables::claves() as $clave) {
            Cache::forget(self::CACHE_PREFIJO.$clave);
        }
    }

    /** @return array<string, array<int, string>> */
    public static function carreras(): array
    {
        return [
            'Ingeniería Civil / Ingeniería Comercial' => [
                'Gestión', 'Operaciones', 'Logística / Cadena de suministros', 'Calidad',
                'Medio Ambiente', 'Proyectos', 'Procesos', 'Mantención', 'Finanzas',
                'Adquisiciones', 'Innovación / Transformación digital',
                'Comercial / Ventas / Marketing', 'Recursos Humanos', 'Consultoría',
                'Construcción', 'Docencia',
            ],
            'Abogado' => [
                'Familia', 'Penal', 'Civil', 'Laboral', 'Comercial y Empresa', 'Tributario',
                'Administrativo', 'Inmobiliario', 'Ambiental', 'Minero', 'Propiedad Intelectual',
                'Internacional', 'Constitucional', 'Consumidor', 'Seguros',
            ],
            'Psicólogo' => [
                'Organizacional / Trabajo', 'Clínica', 'Educacional', 'Social / Comunitaria',
                'Jurídica / Forense', 'Salud', 'Deportiva', 'Gestión',
                'Investigación / Desarrollo', 'Docencia',
            ],
            'Periodista' => ['Comunicaciones corporativas', 'Medios', 'Contenido digital', 'Docencia'],
            'Arquitecto' => ['Diseño', 'Urbanismo', 'Construcción', 'Gestión de proyectos', 'Docencia'],
            'Médico' => ['Medicina general', 'Gestión de salud', 'Salud pública', 'Investigación', 'Docencia'],
            'Otro (salud)' => ['Gestión de salud', 'Atención clínica', 'Salud pública', 'Investigación', 'Docencia'],
        ];
    }

    /** @return list<string> */
    public static function industrias(): array
    {
        return self::desdeCatalogo('industria', 'industrias');
    }

    /** @return list<string> */
    private static function industriasPorDefecto(): array
    {
        return [
            'Minería', 'Agricultura', 'Frutícola', 'Ganadería', 'Silvicultura / Forestal',
            'Pesca / Acuicultura', 'Vitivinícola', 'Alimentos', 'Forestal / Papelera', 'Vinos',
            'Pesquera / Conservas', 'Química', 'Farmacéutica', 'Metalurgia', 'Construcción',
            'Petróleo', 'Generación de Energía', 'Comercio menor / mayor',
            'Banca y servicios financieros', 'Seguros', 'Telecomunicaciones',
            'Transporte / Logística', 'Turismo', 'Salud', 'Educación',
            'Servicios Profesionales (Auditoría / Consultoría / Legales)',
            'Consultora en Recursos Humanos', 'Empleabilidad', 'Selección de Personal', 'Capacitaciones',
            'Tecnología de la Información', 'Inmobiliario', 'Administración Pública',
        ];
    }

    /** @return array<int, string> */
    public static function ciudades(): array
    {
        return [
            'Arica', 'Iquique', 'Antofagasta', 'Copiapó', 'La Serena / Coquimbo',
            'Valparaíso / Viña del Mar', 'Santiago', 'Rancagua', 'Talca', 'Chillán',
            'Concepción', 'Temuco', 'Valdivia', 'Osorno', 'Puerto Montt', 'Coyhaique',
            'Punta Arenas', 'Otra ciudad de Chile',
        ];
    }

    /**
     * Equivalencia histórica: la ficha guardaba ciudades y ahora guarda regiones.
     *
     * @return array<string, string>
     */
    public static function regionPorCiudad(): array
    {
        return [
            'Arica' => 'Arica y Parinacota',
            'Iquique' => 'Tarapacá',
            'Antofagasta' => 'Antofagasta',
            'Copiapó' => 'Atacama',
            'La Serena / Coquimbo' => 'Coquimbo',
            'Valparaíso / Viña del Mar' => 'Valparaíso',
            'Santiago' => 'Metropolitana de Santiago',
            'Rancagua' => "Libertador General Bernardo O'Higgins",
            'Talca' => 'Maule',
            'Chillán' => 'Ñuble',
            'Concepción' => 'Biobío',
            'Temuco' => 'La Araucanía',
            'Valdivia' => 'Los Ríos',
            'Osorno' => 'Los Lagos',
            'Puerto Montt' => 'Los Lagos',
            'Coyhaique' => 'Aysén del General Carlos Ibáñez del Campo',
            'Punta Arenas' => 'Magallanes y de la Antártica Chilena',
        ];
    }

    /** @return list<string> */
    public static function generos(): array
    {
        return self::desdeCatalogo('genero', 'generos');
    }

    /** @return list<string> */
    private static function generosPorDefecto(): array
    {
        return ['Masculino', 'Femenino', 'Prefiero no Informar'];
    }

    /** @return list<string> */
    public static function nacionalidades(): array
    {
        return self::desdeCatalogo('nacionalidad', 'nacionalidades');
    }

    /** @return list<string> */
    private static function nacionalidadesPorDefecto(): array
    {
        return [
            'Chilena', 'Argentina', 'Boliviana', 'Brasileña', 'Colombiana', 'Cubana',
            'Ecuatoriana', 'Española', 'Estadounidense', 'Haitiana', 'Mexicana',
            'Paraguaya', 'Peruana', 'Uruguaya', 'Venezolana', 'Otra',
        ];
    }

    /** @return list<string> */
    public static function paises(): array
    {
        return self::desdeCatalogo('pais', 'paises');
    }

    /** @return list<string> */
    private static function paisesPorDefecto(): array
    {
        // Chile primero por ser el mercado principal; el resto en orden alfabético.
        return [
            'Chile',
            'Afganistán', 'Albania', 'Alemania', 'Andorra', 'Angola', 'Antigua y Barbuda',
            'Arabia Saudita', 'Argelia', 'Argentina', 'Armenia', 'Australia', 'Austria',
            'Azerbaiyán', 'Bahamas', 'Bangladés', 'Barbados', 'Baréin', 'Bélgica', 'Belice',
            'Benín', 'Bielorrusia', 'Bolivia', 'Bosnia y Herzegovina', 'Botsuana', 'Brasil',
            'Brunéi', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Bután', 'Cabo Verde', 'Camboya',
            'Camerún', 'Canadá', 'Catar', 'Chad', 'China', 'Chipre', 'Ciudad del Vaticano',
            'Colombia', 'Comoras', 'Corea del Norte', 'Corea del Sur', 'Costa de Marfil',
            'Costa Rica', 'Croacia', 'Cuba', 'Dinamarca', 'Dominica', 'Ecuador', 'Egipto',
            'El Salvador', 'Emiratos Árabes Unidos', 'Eritrea', 'Eslovaquia', 'Eslovenia',
            'España', 'Estados Unidos', 'Estonia', 'Esuatini', 'Etiopía', 'Filipinas',
            'Finlandia', 'Fiyi', 'Francia', 'Gabón', 'Gambia', 'Georgia', 'Ghana', 'Granada',
            'Grecia', 'Guatemala', 'Guinea', 'Guinea-Bisáu', 'Guinea Ecuatorial', 'Guyana',
            'Haití', 'Honduras', 'Hungría', 'India', 'Indonesia', 'Irak', 'Irán', 'Irlanda',
            'Islandia', 'Islas Marshall', 'Islas Salomón', 'Israel', 'Italia', 'Jamaica',
            'Japón', 'Jordania', 'Kazajistán', 'Kenia', 'Kirguistán', 'Kiribati', 'Kuwait',
            'Laos', 'Lesoto', 'Letonia', 'Líbano', 'Liberia', 'Libia', 'Liechtenstein',
            'Lituania', 'Luxemburgo', 'Macedonia del Norte', 'Madagascar', 'Malasia', 'Malaui',
            'Maldivas', 'Malí', 'Malta', 'Marruecos', 'Mauricio', 'Mauritania', 'México',
            'Micronesia', 'Moldavia', 'Mónaco', 'Mongolia', 'Montenegro', 'Mozambique',
            'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Nicaragua', 'Níger', 'Nigeria', 'Noruega',
            'Nueva Zelanda', 'Omán', 'Países Bajos', 'Pakistán', 'Palaos', 'Palestina',
            'Panamá', 'Papúa Nueva Guinea', 'Paraguay', 'Perú', 'Polonia', 'Portugal',
            'Reino Unido', 'República Centroafricana', 'República Checa',
            'República del Congo', 'República Democrática del Congo', 'República Dominicana',
            'Ruanda', 'Rumania', 'Rusia', 'Samoa', 'San Cristóbal y Nieves', 'San Marino',
            'San Vicente y las Granadinas', 'Santa Lucía', 'Santo Tomé y Príncipe', 'Senegal',
            'Serbia', 'Seychelles', 'Sierra Leona', 'Singapur', 'Siria', 'Somalia', 'Sri Lanka',
            'Sudáfrica', 'Sudán', 'Sudán del Sur', 'Suecia', 'Suiza', 'Surinam', 'Tailandia',
            'Tanzania', 'Tayikistán', 'Timor Oriental', 'Togo', 'Tonga', 'Trinidad y Tobago',
            'Túnez', 'Turkmenistán', 'Turquía', 'Tuvalu', 'Ucrania', 'Uganda', 'Uruguay',
            'Uzbekistán', 'Vanuatu', 'Venezuela', 'Vietnam', 'Yemen', 'Yibuti', 'Zambia',
            'Zimbabue',
        ];
    }

    /** @return list<string> */
    public static function situacionesLaborales(): array
    {
        return self::desdeCatalogo('situacion_laboral', 'situacionesLaborales');
    }

    /** @return list<string> */
    private static function situacionesLaboralesPorDefecto(): array
    {
        return [
            'Trabajando actualmente', 'Buscando trabajo',
            'Independiente / Honorarios',
        ];
    }

    /** @return array<int, string> */
    public static function regiones(): array
    {
        return [
            'Arica y Parinacota', 'Tarapacá', 'Antofagasta', 'Atacama', 'Coquimbo',
            'Valparaíso', 'Metropolitana de Santiago', "Libertador General Bernardo O'Higgins",
            'Maule', 'Ñuble', 'Biobío', 'La Araucanía', 'Los Ríos', 'Los Lagos',
            'Aysén del General Carlos Ibáñez del Campo', 'Magallanes y de la Antártica Chilena',
        ];
    }

    /** @return list<string> */
    public static function regionesInteres(): array
    {
        return self::desdeCatalogo('region', 'regionesInteres');
    }

    /** @return list<string> */
    private static function regionesInteresPorDefecto(): array
    {
        $prioritarias = ['Metropolitana de Santiago', 'Biobío', 'Valparaíso'];
        $resto = array_values(array_diff(self::regiones(), $prioritarias));

        return array_merge(['Nacional', 'Internacional'], $prioritarias, $resto);
    }

    /** @return list<string> */
    public static function modalidadesTrabajoPreferidas(): array
    {
        return self::desdeCatalogo('modalidad_trabajo', 'modalidadesTrabajoPreferidas');
    }

    /** @return list<string> */
    private static function modalidadesTrabajoPreferidasPorDefecto(): array
    {
        return ['Jornada Completa', 'Jornada Parcial', 'Honorarios'];
    }

    /** @return list<string> */
    public static function cargosAreas(): array
    {
        return self::desdeCatalogo('cargo_area', 'cargosAreas');
    }

    /** @return list<string> */
    private static function cargosAreasPorDefecto(): array
    {
        return [
            'Gerencia General', 'Administración y Finanzas', 'Finanzas', 'Control de Gestión',
            'Contabilidad', 'Operaciones', 'Logística / Cadena de suministros', 'Proyectos',
            'Procesos', 'Mantención', 'Calidad', 'Medio Ambiente', 'Adquisiciones',
            'Comercial / Ventas / Marketing', 'Recursos Humanos', 'Legal', 'Consultoría',
            'Construcción', 'Tecnología / Transformación digital', 'Salud', 'Educación / Docencia',
        ];
    }

    /** @return list<string> */
    public static function instituciones(): array
    {
        return self::desdeCatalogo('institucion', 'instituciones');
    }

    /** @return list<string> */
    private static function institucionesPorDefecto(): array
    {
        return require __DIR__.'/instituciones.php';
    }

    /** @return list<string> */
    public static function empresas(): array
    {
        return self::desdeCatalogo('empresa', 'empresas');
    }

    /** @return list<string> */
    private static function empresasPorDefecto(): array
    {
        return array_values(['Otra', ...require __DIR__.'/empresas.php']);
    }

    /** @return list<string> */
    public static function cargos(): array
    {
        return self::desdeCatalogo('cargo', 'cargos');
    }

    /** @return list<string> */
    private static function cargosPorDefecto(): array
    {
        return array_values(['Otros', ...require __DIR__.'/cargos.php']);
    }

    /** @return list<string> */
    public static function habilidades(): array
    {
        return self::desdeCatalogo('habilidad', 'habilidades');
    }

    /** @return list<string> */
    private static function habilidadesPorDefecto(): array
    {
        return require __DIR__.'/habilidades.php';
    }

    /** @return list<string> */
    public static function carrerasEstudio(): array
    {
        return self::desdeCatalogo('carrera', 'carrerasEstudio');
    }

    /** @return list<string> */
    private static function carrerasEstudioPorDefecto(): array
    {
        return require __DIR__.'/carreras_estudio.php';
    }

    /**
     * Límites del filtro de edad. El tope se interpreta como "o más", así que un
     * postulante de 90 años sigue calzando en una búsqueda que llega hasta el máximo.
     *
     * @return array{min: int, max: int}
     */
    public static function rangoEdad(): array
    {
        return ['min' => 50, 'max' => 80];
    }

    /** @return array{min: int, max: int} */
    public static function rangoExperiencia(): array
    {
        return ['min' => 0, 'max' => 40];
    }

    /**
     * Límites del filtro de sueldo, expresados en MILLONES de pesos para que el
     * deslizador avance de a un intervalo entero. El tope se interpreta como "o más":
     * una oferta de $12.000.000 sigue calzando en un rango que llega al máximo.
     *
     * @return array{min: int, max: int}
     */
    public static function rangoSueldo(): array
    {
        return ['min' => 0, 'max' => 8];
    }

    /** Pesos que representa un punto del deslizador de sueldo. */
    public const SUELDO_POR_INTERVALO = 1_000_000;

    /** @return array<int, string> */
    public static function rangosExperiencia(): array
    {
        return [
            0 => 'Sin mínimo',
            5 => '5 años o más',
            10 => '10 años o más',
            15 => '15 años o más',
            20 => '20 años o más',
            25 => '25 años o más',
            30 => '30 años o más',
        ];
    }

    /** @return list<string> */
    public static function tiposTrabajo(): array
    {
        return self::desdeCatalogo('tipo_trabajo', 'tiposTrabajo');
    }

    /** @return list<string> */
    private static function tiposTrabajoPorDefecto(): array
    {
        return [
            'Jornada completa', 'Media jornada', 'Independiente', 'Contrato temporal',
            'Por proyecto', 'Consultoría',
        ];
    }

    /** @return list<string> */
    public static function jerarquias(): array
    {
        return self::desdeCatalogo('jerarquia', 'jerarquias');
    }

    /** @return list<string> */
    private static function jerarquiasPorDefecto(): array
    {
        return [
            'Gerencia / Dirección', 'Subgerencia', 'Jefatura', 'Coordinación / Supervisión',
            'Profesional / Especialista', 'Técnico', 'Administrativo', 'Operativo',
        ];
    }

    /** @return array<int, string> */
    public static function meses(): array
    {
        return [
            1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
        ];
    }

    /** @return list<string> */
    public static function nivelesEstudio(): array
    {
        return self::desdeCatalogo('nivel_estudios', 'nivelesEstudio');
    }

    /** @return list<string> */
    private static function nivelesEstudioPorDefecto(): array
    {
        return [
            'CFT / Instituto Profesional', 'Título Profesional', 'Diplomado / Postítulo',
            'Magíster', 'Especialidad Área Salud', 'Doctorado', 'Otro',
        ];
    }

    /**
     * Niveles escolares (básica, media): ya no se ofrecen.
     *
     * La ficha arranca en el título profesional, así que la lista quedó vacía a
     * propósito. Se conserva el método porque de él cuelga el formulario, que para un
     * nivel escolar pedía año de egreso en vez de carrera y fechas: con la lista vacía
     * esa rama simplemente no se activa nunca.
     *
     * @return array<int, string>
     */
    public static function nivelesEscolares(): array
    {
        return [];
    }

    /**
     * Niveles que se consideran postgrado, para rellenar `postulantes.postgrado`.
     *
     * @return array<int, string>
     */
    public static function nivelesDePostgrado(): array
    {
        return ['Diplomado / Postítulo', 'Magíster', 'Especialidad Área Salud', 'Doctorado'];
    }

    /** @return array<int, string> */
    public static function modalidadesEstudio(): array
    {
        return ['Presencial', 'Semi-presencial', 'Online'];
    }

    /** @return list<string> */
    public static function situacionesEstudio(): array
    {
        return self::desdeCatalogo('situacion_estudios', 'situacionesEstudio');
    }

    /** @return list<string> */
    private static function situacionesEstudioPorDefecto(): array
    {
        return ['Titulado/a', 'Egresado/a', 'Estudiando', 'Incompleto'];
    }

    /** @return list<string> */
    public static function idiomas(): array
    {
        return self::desdeCatalogo('idioma', 'idiomas');
    }

    /** @return list<string> */
    private static function idiomasPorDefecto(): array
    {
        return [
            // Los cinco más habituales primero, para no obligar a bajar por la lista;
            // el resto en orden alfabético.
            'Español', 'Inglés', 'Portugués', 'Francés', 'Alemán',
            'Chino Mandarín', 'Coreano', 'Italiano', 'Japonés', 'Mapudungun', 'Polaco', 'Ruso',
        ];
    }

    /** @return list<string> */
    public static function nivelesIdioma(): array
    {
        return self::desdeCatalogo('nivel_idioma', 'nivelesIdioma');
    }

    /** @return list<string> */
    private static function nivelesIdiomaPorDefecto(): array
    {
        return ['Bilingüe / Nativo', 'Avanzado', 'Intermedio'];
    }

    /**
     * Combinaciones "Idioma · Nivel" para filtrar procesos por idioma y nivel a la vez.
     *
     * @return list<string>
     */
    public static function idiomasConNivel(): array
    {
        $combos = [];

        foreach (self::idiomas() as $idioma) {
            foreach (self::nivelesIdioma() as $nivel) {
                $combos[] = $idioma.' · '.$nivel;
            }
        }

        return $combos;
    }

    /** @return array<int, string> */
    public static function especialidades(?string $carrera): array
    {
        return self::carreras()[$carrera] ?? [];
    }
}
