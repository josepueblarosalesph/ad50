<?php

namespace App\Support;

class CatalogosProfesionales
{
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

    /** @return array<int, string> */
    public static function industrias(): array
    {
        return [
            'Minería', 'Agricultura', 'Frutícola', 'Ganadería', 'Silvicultura / Forestal',
            'Pesca / Acuicultura', 'Vitivinícola', 'Alimentos', 'Forestal / Papelera', 'Vinos',
            'Pesquera / Conservas', 'Química', 'Farmacéutica', 'Metalurgia', 'Construcción',
            'Petróleo', 'Generación de Energía', 'Comercio menor / mayor',
            'Banca y servicios financieros', 'Seguros', 'Telecomunicaciones',
            'Transporte / Logística', 'Turismo', 'Salud', 'Educación',
            'Servicios Profesionales (Auditoría / Consultoría / Legales)',
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

    /** @return array<int, string> */
    public static function generos(): array
    {
        return ['Masculino', 'Femenino', 'Prefiero no Informar'];
    }

    /** @return array<int, string> */
    public static function nacionalidades(): array
    {
        return [
            'Chilena', 'Argentina', 'Boliviana', 'Brasileña', 'Colombiana', 'Cubana',
            'Ecuatoriana', 'Española', 'Estadounidense', 'Haitiana', 'Mexicana',
            'Paraguaya', 'Peruana', 'Uruguaya', 'Venezolana', 'Otra',
        ];
    }

    /** @return array<int, string> */
    public static function situacionesLaborales(): array
    {
        return [
            'Trabajando actualmente', 'Buscando trabajo',
            'Independiente / Honorarios', 'Jubilado',
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

    /** @return array<int, string> */
    public static function modalidadesTrabajoPreferidas(): array
    {
        return ['Jornada Completa', 'Jornada Parcial', 'Honorarios'];
    }

    /** @return array<int, string> */
    public static function cargosAreas(): array
    {
        return [
            'Gerencia General', 'Administración y Finanzas', 'Finanzas', 'Control de Gestión',
            'Contabilidad', 'Operaciones', 'Logística / Cadena de suministros', 'Proyectos',
            'Procesos', 'Mantención', 'Calidad', 'Medio Ambiente', 'Adquisiciones',
            'Comercial / Ventas / Marketing', 'Recursos Humanos', 'Legal', 'Consultoría',
            'Construcción', 'Tecnología / Transformación digital', 'Salud', 'Educación / Docencia',
        ];
    }

    /** @return array<int, string> */
    public static function instituciones(): array
    {
        return require __DIR__.'/instituciones.php';
    }

    /** @return array<int, string> */
    public static function carrerasEstudio(): array
    {
        return require __DIR__.'/carreras_estudio.php';
    }

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

    /** @return array<int, string> */
    public static function tiposTrabajo(): array
    {
        return [
            'Jornada completa', 'Media jornada', 'Independiente', 'Contrato temporal',
            'Práctica', 'Por proyecto', 'Consultoría',
        ];
    }

    /** @return array<int, string> */
    public static function jerarquias(): array
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

    /** @return array<int, string> */
    public static function nivelesEstudio(): array
    {
        return [
            'Básica', 'Media', 'Técnico Medio / Colegio Técnico',
            'Técnico profesional superior', 'Universitaria', 'Diplomado',
            'Postgrado', 'Magíster', 'Doctorado', 'Otro',
        ];
    }

    /** @return array<int, string> */
    public static function nivelesEscolares(): array
    {
        return ['Básica', 'Media', 'Técnico Medio / Colegio Técnico'];
    }

    /** @return array<int, string> */
    public static function modalidadesEstudio(): array
    {
        return ['Presencial', 'Semi-presencial', 'Online'];
    }

    /** @return array<int, string> */
    public static function situacionesEstudio(): array
    {
        return ['Egresado', 'Titulado', 'Estudiando', 'Incompleto'];
    }

    /** @return array<int, string> */
    public static function idiomas(): array
    {
        return [
            'Alemán', 'Chino Mandarín', 'Coreano', 'Español', 'Francés', 'Inglés',
            'Italiano', 'Japonés', 'Mapudungun', 'Polaco', 'Portugués', 'Ruso',
        ];
    }

    /** @return array<int, string> */
    public static function nivelesIdioma(): array
    {
        return ['Medio', 'Alto'];
    }

    /** @return array<int, string> */
    public static function especialidades(?string $carrera): array
    {
        return self::carreras()[$carrera] ?? [];
    }
}
