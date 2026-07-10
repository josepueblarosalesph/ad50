<?php

namespace Database\Seeders;

use App\Models\Postulante;
use App\Models\User;
use App\Support\CatalogosProfesionales;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PostulanteSeeder extends Seeder
{
    public function run(): void
    {
        $perfiles = $this->perfiles();
        $ahora = now();
        $password = Hash::make('password');

        User::query()->upsert(
            collect($perfiles)->map(fn (array $perfil): array => [
                'name' => $perfil['name'],
                'email' => $perfil['email'],
                'email_verified_at' => $ahora,
                'password' => $password,
                'role' => 'postulante',
                'acepta_ley_21719' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->all(),
            ['email'],
            ['name', 'email_verified_at', 'role', 'acepta_ley_21719', 'updated_at'],
        );

        $usuarios = User::query()->whereIn('email', collect($perfiles)->pluck('email'))->pluck('id', 'email');
        $postulantesExistentes = Postulante::query()->whereIn('user_id', $usuarios)->pluck('id', 'user_id');
        $siguienteId = (Postulante::query()->max('id') ?? 0) + 1;
        $postulantes = collect($perfiles)->map(function (array $perfil, int $index) use ($usuarios, $postulantesExistentes, $ahora, &$siguienteId): array {
            $atributos = $this->atributosPostulante($perfil, $index);
            $userId = $usuarios[$perfil['email']];

            foreach (['educaciones', 'idiomas', 'experiencias', 'modalidad_trabajo', 'regiones_interes', 'industrias_interes'] as $atributoJson) {
                $atributos[$atributoJson] = json_encode($atributos[$atributoJson], JSON_THROW_ON_ERROR);
            }

            return [
                'id' => $postulantesExistentes[$userId] ?? $siguienteId++,
                'user_id' => $userId,
                ...$atributos,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        })->all();

        Postulante::query()->upsert(
            $postulantes,
            ['id'],
            array_values(array_diff(array_keys($postulantes[0]), ['id', 'user_id', 'created_at'])),
        );
    }

    /**
     * @param  array<string, mixed>  $perfil
     * @return array<string, mixed>
     */
    private function atributosPostulante(array $perfil, int $index): array
    {
        $anioInicio = now()->year - $perfil['anios_experiencia'];

        return [
            'rut' => sprintf('1%d.%03d.%03d-%d', ($index % 8) + 1, 100 + $index, 200 + $index, $index % 10),
            'anio_nacimiento' => now()->year - $perfil['edad'],
            'genero' => $perfil['genero'],
            'titular' => $perfil['titular'],
            'telefono' => '+56 9 '.str_pad((string) (61000000 + $index), 8, '0', STR_PAD_LEFT),
            'linkedin' => 'https://linkedin.com/in/'.$perfil['slug'],
            'ciudad' => CatalogosProfesionales::regionPorCiudad()[$perfil['ciudad']] ?? null,
            'regiones_interes' => array_values($perfil['regiones']),
            'modalidad_trabajo' => [$perfil['modalidad']],
            'cargo_actual' => $perfil['cargo'],
            'industrias_interes' => array_values(array_filter([$perfil['industria'], $perfil['industria_2'] ?? null, $perfil['industria_3'] ?? null])),
            'carrera' => $perfil['carrera'],
            'universidad' => $perfil['institucion'],
            'especialidad' => $perfil['especialidad'],
            'postgrado' => $perfil['postgrado'] ?? null,
            'educaciones' => $this->educaciones($perfil, $anioInicio),
            'idiomas' => $this->idiomas($perfil['idiomas']),
            'empresa_actual' => $perfil['empresa'],
            'experiencia_area' => $perfil['cargo'],
            'experiencia_inicio' => $anioInicio,
            'experiencia_fin' => null,
            'experiencias' => $this->experiencias($perfil, $anioInicio),
            'resumen_profesional' => $perfil['resumen'],
            'anios_experiencia' => $perfil['anios_experiencia'],
            'completitud' => 100,
            'visible' => true,
            'suscripcion_hasta' => now()->addYear(),
        ];
    }

    /** @param array<string, mixed> $perfil
     * @return list<array<string, mixed>>
     */
    private function educaciones(array $perfil, int $anioInicio): array
    {
        $educaciones = [[
            'nivel' => 'Universitaria',
            'pais' => 'Chile',
            'institucion' => $perfil['institucion'],
            'carrera' => $perfil['carrera'],
            'mencion' => $perfil['especialidad'],
            'modalidad' => 'Presencial',
            'situacion' => 'Titulado',
            'inicio_anio' => $anioInicio - 5,
            'termino_anio' => $anioInicio,
            'egreso_anio' => null,
        ]];

        if (isset($perfil['postgrado'])) {
            $educaciones[] = [
                'nivel' => 'Magíster',
                'pais' => 'Chile',
                'institucion' => $perfil['institucion_postgrado'] ?? 'Universidad Adolfo Ibáñez',
                'carrera' => $perfil['postgrado'],
                'mencion' => 'Gestión',
                'modalidad' => 'Semi-presencial',
                'situacion' => 'Titulado',
                'inicio_anio' => $anioInicio + 7,
                'termino_anio' => $anioInicio + 9,
                'egreso_anio' => null,
            ];
        }

        return $educaciones;
    }

    /** @param list<string> $idiomas
     * @return list<array{idioma: string, nivel: string}>
     */
    private function idiomas(array $idiomas): array
    {
        return collect($idiomas)
            ->map(fn (string $idioma, int $index): array => [
                'idioma' => $idioma,
                'nivel' => $index === 0 || $idioma === 'Español' ? 'Alto' : 'Medio',
            ])
            ->all();
    }

    /** @param array<string, mixed> $perfil
     * @return list<array<string, mixed>>
     */
    private function experiencias(array $perfil, int $anioInicio): array
    {
        return [
            [
                'cargo' => $perfil['cargo'],
                'area' => $perfil['cargo'],
                'tipo_trabajo' => 'Jornada completa',
                'empresa' => $perfil['empresa'],
                'jerarquia' => $perfil['jerarquia'],
                'actividad_empresa' => $perfil['industria'],
                'inicio_mes' => 1,
                'inicio_anio' => now()->year - 6,
                'actualmente' => true,
                'fin_mes' => null,
                'fin_anio' => null,
                'responsabilidades' => $perfil['responsabilidades'],
            ],
            [
                'cargo' => $perfil['cargo_anterior'],
                'area' => $perfil['cargo'],
                'tipo_trabajo' => 'Jornada completa',
                'empresa' => $perfil['empresa_anterior'],
                'jerarquia' => 'Jefatura',
                'actividad_empresa' => $industria = ($perfil['industria_2'] ?? $perfil['industria']),
                'inicio_mes' => 3,
                'inicio_anio' => $anioInicio,
                'actualmente' => false,
                'fin_mes' => 12,
                'fin_anio' => now()->year - 7,
                'responsabilidades' => 'Lideró equipos multidisciplinarios, indicadores y mejora continua en '.$industria.'.',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function perfiles(): array
    {
        return [
            $this->perfil('María José Fuentes', 'maria@adconsulting.cl', 'maria-jose-fuentes', 55, 'Mujer', 'Finanzas', 'Subgerenta de Finanzas y transformación empresarial', 'Concepción', ['Biobío', 'Ñuble'], 'Jornada Completa', 'Ingeniería Civil / Ingeniería Comercial', 'Finanzas', 'Universidad de Concepción', 'Banca y servicios financieros', ['Forestal / Papelera'], 18, 'Forestal del Biobío', 'Jefatura de Control de Gestión', 'Banco Regional', 'Subgerencia', 'Lidera transformación financiera, planificación, control de gestión y equipos regionales.', ['Español', 'Inglés'], 'Magíster en Finanzas'),
            $this->perfil('Carlos Eduardo Mena', 'carlos.mena@example.com', 'carlos-mena', 59, 'Hombre', 'Operaciones', 'Director de operaciones mineras', 'Santiago', ['Metropolitana de Santiago', 'Antofagasta'], 'Jornada Completa', 'Ingeniería Civil / Ingeniería Comercial', 'Operaciones', 'Universidad de Santiago de Chile', 'Minería', ['Metalurgia'], 25, 'Minerales Andinos', 'Jefe de Planta', 'Cobre Norte', 'Gerencia / Dirección', 'Gestión de excelencia operacional, seguridad y productividad en faenas mineras.', ['Español', 'Inglés'], 'MBA Ejecutivo'),
            $this->perfil('Ana María Villarroel', 'ana.villarroel@example.com', 'ana-villarroel', 52, 'Mujer', 'Recursos Humanos', 'Gerenta de personas y cultura', 'Valparaíso / Viña del Mar', ['Valparaíso', 'Metropolitana de Santiago'], 'Jornada Completa', 'Psicólogo', 'Organizacional / Trabajo', 'Pontificia Universidad Católica de Valparaíso', 'Servicios Profesionales (Auditoría / Consultoría / Legales)', ['Educación'], 16, 'Talento Pacífico', 'Jefa de Desarrollo Organizacional', 'Universidad Costera', 'Gerencia / Dirección', 'Diseña estrategias de cultura, cambio organizacional, inclusión y desarrollo de liderazgo.', ['Español', 'Inglés']),
            $this->perfil('Jorge Ignacio Rivas', 'jorge.rivas@example.com', 'jorge-rivas', 60, 'Hombre', 'Legal', 'Abogado corporativo y director legal', 'Santiago', ['Metropolitana de Santiago'], 'Honorarios', 'Abogado', 'Comercial y Empresa', 'Universidad de Chile', 'Servicios Profesionales (Auditoría / Consultoría / Legales)', ['Banca y servicios financieros'], 27, 'Rivas Legal', 'Fiscal Corporativo', 'Grupo Financiero Central', 'Gerencia / Dirección', 'Asesora gobiernos corporativos, contratos complejos, compliance y negociación estratégica.', ['Español', 'Inglés'], 'Magíster en Derecho de la Empresa'),
            $this->perfil('Patricia Lagos Sepúlveda', 'patricia.lagos@example.com', 'patricia-lagos', 57, 'Mujer', 'Salud', 'Directora médica y gestora de salud', 'Concepción', ['Biobío', 'Ñuble', 'La Araucanía'], 'Jornada Parcial', 'Médico', 'Gestión de salud', 'Universidad de Concepción', 'Salud', ['Administración Pública'], 23, 'Red Clínica del Sur', 'Jefa de Calidad Clínica', 'Hospital Regional', 'Gerencia / Dirección', 'Lidera calidad asistencial, acreditación, experiencia paciente y transformación de servicios clínicos.', ['Español', 'Inglés'], 'Magíster en Salud Pública'),
            $this->perfil('Luis Alberto Zamora', 'luis.zamora@example.com', 'luis-zamora', 51, 'Hombre', 'Tecnología / Transformación digital', 'Gerente de transformación digital', 'Santiago', ['Metropolitana de Santiago', 'Biobío'], 'Jornada Completa', 'Ingeniería Civil / Ingeniería Comercial', 'Innovación / Transformación digital', 'Universidad Técnica Federico Santa María', 'Tecnología de la Información', ['Telecomunicaciones'], 19, 'NovaTech Chile', 'Jefe de Arquitectura Empresarial', 'Telecom Sur', 'Gerencia / Dirección', 'Conduce transformación digital, analítica de datos, automatización y adopción tecnológica.', ['Español', 'Inglés', 'Portugués'], 'Magíster en Tecnologías de Información'),
            $this->perfil('Marcela Soto Arancibia', 'marcela.soto@example.com', 'marcela-soto', 54, 'Mujer', 'Comercial / Ventas / Marketing', 'Gerenta comercial de consumo masivo', 'Puerto Montt', ['Los Lagos', 'Los Ríos'], 'Jornada Completa', 'Ingeniería Civil / Ingeniería Comercial', 'Comercial / Ventas / Marketing', 'Universidad Austral de Chile', 'Alimentos', ['Pesca / Acuicultura'], 21, 'Alimentos Patagonia', 'Jefa Comercial', 'Acuícola Austral', 'Gerencia / Dirección', 'Desarrolla estrategia comercial, canales, exportaciones y crecimiento rentable en mercados regionales.', ['Español', 'Inglés']),
            $this->perfil('Rodrigo Pérez Araya', 'rodrigo.perez@example.com', 'rodrigo-perez', 58, 'Hombre', 'Logística / Cadena de suministros', 'Gerente de logística y abastecimiento', 'Antofagasta', ['Antofagasta', 'Tarapacá'], 'Jornada Completa', 'Ingeniería Civil / Ingeniería Comercial', 'Logística / Cadena de suministros', 'Universidad Católica del Norte', 'Transporte / Logística', ['Minería'], 26, 'Logística Pacífico', 'Jefe de Abastecimiento', 'Servicios Mineros Norte', 'Gerencia / Dirección', 'Optimiza cadena de suministro, contratos, inventarios y continuidad operacional para minería.', ['Español', 'Inglés']),
            $this->perfil('Cecilia Antilef Huenchumán', 'cecilia.antilef@example.com', 'cecilia-antilef', 53, 'Mujer', 'Educación / Docencia', 'Directora de formación y aprendizaje', 'Temuco', ['La Araucanía', 'Los Ríos'], 'Jornada Parcial', 'Psicólogo', 'Educacional', 'Universidad de La Frontera', 'Educación', ['Administración Pública'], 18, 'Fundación Aprender', 'Coordinadora Académica', 'Instituto Araucanía', 'Gerencia / Dirección', 'Impulsa innovación educativa, formación de equipos, convivencia e inclusión intercultural.', ['Español', 'Mapudungun', 'Inglés'], 'Magíster en Educación'),
            $this->perfil('Andrés Molina Bustos', 'andres.molina@example.com', 'andres-molina', 56, 'Hombre', 'Construcción', 'Director de proyectos de construcción', 'Rancagua', ["Libertador General Bernardo O'Higgins", 'Metropolitana de Santiago'], 'Honorarios', 'Arquitecto', 'Construcción', 'Universidad de Valparaíso', 'Construcción', ['Inmobiliario'], 24, 'Molina Proyectos', 'Jefe de Proyectos', 'Desarrollo Urbano SpA', 'Gerencia / Dirección', 'Dirige proyectos inmobiliarios, coordinación técnica, permisos y control de costos de construcción.', ['Español', 'Inglés']),
            $this->perfil('Paula Andrea Neira', 'paula.neira@example.com', 'paula-neira', 50, 'Mujer', 'Medio Ambiente', 'Especialista senior en sostenibilidad', 'Valdivia', ['Los Ríos', 'Los Lagos', 'La Araucanía'], 'Jornada Completa', 'Ingeniería Civil / Ingeniería Comercial', 'Medio Ambiente', 'Universidad Austral de Chile', 'Generación de Energía', ['Silvicultura / Forestal'], 16, 'Energía Verde Sur', 'Jefa Ambiental', 'Bosques Sustentables', 'Profesional / Especialista', 'Gestiona sostenibilidad, evaluación ambiental, relacionamiento territorial y economía circular.', ['Español', 'Inglés'], 'Magíster en Gestión Ambiental'),
            $this->perfil('Héctor Salinas Cortés', 'hector.salinas@example.com', 'hector-salinas', 62, 'Hombre', 'Gerencia General', 'Gerente general y asesor de directorios', 'Iquique', ['Tarapacá', 'Antofagasta'], 'Honorarios', 'Ingeniería Civil / Ingeniería Comercial', 'Gestión', 'Universidad de Chile', 'Minería', ['Comercio menor / mayor'], 32, 'Asesorías Salinas', 'Gerente de Operaciones', 'Minería del Norte', 'Gerencia / Dirección', 'Acompaña estrategia, gobierno corporativo, expansión, productividad y gestión de crisis.', ['Español', 'Inglés'], 'MBA'),
            $this->perfil('Daniela Contreras Vidal', 'daniela.contreras@example.com', 'daniela-contreras', 49, 'Mujer', 'Consultoría', 'Consultora senior en comunicaciones estratégicas', 'Concepción', ['Biobío', 'Metropolitana de Santiago'], 'Honorarios', 'Periodista', 'Comunicaciones corporativas', 'Universidad de Concepción', 'Servicios Profesionales (Auditoría / Consultoría / Legales)', ['Telecomunicaciones'], 17, 'DC Estrategia', 'Jefa de Comunicaciones', 'Corporación Regional', 'Profesional / Especialista', 'Asesora reputación, comunicación de crisis, asuntos públicos y relato corporativo.', ['Español', 'Inglés']),
            $this->perfil('Sebastián Fuenzalida Mora', 'sebastian.fuenzalida@example.com', 'sebastian-fuenzalida', 48, 'Hombre', 'Administración y Finanzas', 'Jefe de administración y finanzas', 'Chillán', ['Ñuble', 'Biobío', 'Maule'], 'Jornada Completa', 'Ingeniería Civil / Ingeniería Comercial', 'Finanzas', 'Universidad del Bío-Bío', 'Agricultura', ['Alimentos'], 15, 'AgroÑuble', 'Controller', 'Exportadora del Valle', 'Jefatura', 'Gestiona presupuesto, tesorería, control interno y transformación de procesos administrativos.', ['Español', 'Inglés']),
            $this->perfil('Francisca Olivares Díaz', 'francisca.olivares@example.com', 'francisca-olivares', 55, 'Mujer', 'Proyectos', 'Directora de proyectos inmobiliarios', 'La Serena / Coquimbo', ['Coquimbo', 'Valparaíso'], 'Jornada Completa', 'Arquitecto', 'Gestión de proyectos', 'Universidad de La Serena', 'Inmobiliario', ['Construcción'], 22, 'Desarrollos Elqui', 'Jefa de Arquitectura', 'Constructora Costa', 'Gerencia / Dirección', 'Lidera portafolios, planificación, permisos, diseño y coordinación de proyectos inmobiliarios.', ['Español', 'Inglés']),
            $this->perfil('Miguel Ángel Rojas', 'miguel.rojas@example.com', 'miguel-rojas', 59, 'Hombre', 'Mantención', 'Gerente de mantenimiento industrial', 'Talca', ['Maule', "Libertador General Bernardo O'Higgins"], 'Jornada Completa', 'Ingeniería Civil / Ingeniería Comercial', 'Mantención', 'Universidad de Talca', 'Alimentos', ['Agricultura'], 28, 'Procesos del Maule', 'Jefe de Mantenimiento', 'Agroindustria Central', 'Gerencia / Dirección', 'Implementa confiabilidad, mantenimiento predictivo, seguridad y mejora continua de plantas.', ['Español', 'Inglés']),
            $this->perfil('Rosa Elena Cárdenas', 'rosa.cardenas@example.com', 'rosa-cardenas', 52, 'Mujer', 'Calidad', 'Jefa senior de calidad y cumplimiento', 'Osorno', ['Los Lagos', 'Los Ríos'], 'Jornada Completa', 'Ingeniería Civil / Ingeniería Comercial', 'Calidad', 'Universidad de Los Lagos', 'Farmacéutica', ['Alimentos'], 18, 'Laboratorios Austral', 'Supervisora de Calidad', 'Nutrición del Sur', 'Jefatura', 'Gestiona sistemas de calidad, auditorías, cumplimiento regulatorio y mejora de procesos.', ['Español', 'Inglés']),
            $this->perfil('Alex Rivera Soto', 'alex.rivera@example.com', 'alex-rivera', 47, 'No binario', 'Consultoría', 'Consultor de cambio y desarrollo organizacional', 'Santiago', ['Metropolitana de Santiago', 'Valparaíso'], 'Honorarios', 'Psicólogo', 'Gestión', 'Universidad Diego Portales', 'Servicios Profesionales (Auditoría / Consultoría / Legales)', ['Tecnología de la Información'], 14, 'Cambio Humano Consultores', 'Consultor Senior', 'PeopleTech', 'Profesional / Especialista', 'Facilita transformación cultural, diseño organizacional, liderazgo e inclusión.', ['Español', 'Inglés', 'Francés']),
        ];
    }

    /** @return array<string, mixed> */
    private function perfil(string $name, string $email, string $slug, int $edad, string $genero, string $cargo, string $titular, string $ciudad, array $regiones, string $modalidad, string $carrera, string $especialidad, string $institucion, string $industria, array $industriasAdicionales, int $aniosExperiencia, string $empresa, string $cargoAnterior, string $empresaAnterior, string $jerarquia, string $resumen, array $idiomas, ?string $postgrado = null): array
    {
        $genero = match ($genero) {
            'Mujer' => 'Femenino',
            'Hombre' => 'Masculino',
            default => 'Prefiero no Informar',
        };

        return [
            'name' => $name, 'email' => $email, 'slug' => $slug, 'edad' => $edad, 'genero' => $genero,
            'cargo' => $cargo, 'titular' => $titular, 'ciudad' => $ciudad, 'regiones' => $regiones,
            'modalidad' => $modalidad, 'carrera' => $carrera, 'especialidad' => $especialidad,
            'institucion' => $institucion, 'industria' => $industria,
            'industria_2' => $industriasAdicionales[0] ?? null, 'industria_3' => $industriasAdicionales[1] ?? null,
            'anios_experiencia' => $aniosExperiencia, 'empresa' => $empresa, 'cargo_anterior' => $cargoAnterior,
            'empresa_anterior' => $empresaAnterior, 'jerarquia' => $jerarquia, 'resumen' => $resumen,
            'responsabilidades' => $resumen, 'idiomas' => $idiomas, 'postgrado' => $postgrado,
        ];
    }
}
