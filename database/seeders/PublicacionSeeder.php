<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Publicacion;
use Illuminate\Database\Seeder;

class PublicacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresas = Empresa::query()->orderBy('id')->get();

        if ($empresas->isEmpty()) {
            return;
        }

        foreach ($this->publicaciones() as $index => $datos) {
            $empresa = $empresas[$index % $empresas->count()];

            Publicacion::query()->updateOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'cargo' => $datos['cargo'],
                ],
                [
                    ...$datos,
                    'nombre_empresa' => $empresa->razon_social,
                    'vigente_hasta' => today()->addDays($datos['vigencia_dias']),
                ],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function publicaciones(): array
    {
        $base = [
            'tipo_cargo' => 'Jornada completa',
            'vacantes' => 1,
            'pais' => 'Chile',
            'mostrar_sueldo' => true,
            'estudios_minimos' => 'Título Profesional',
            'situacion_academica' => 'Titulado/a',
            'idiomas' => ['Español'],
            'preguntas' => ['¿Qué experiencia relevante aportarías a esta posición?'],
            'empleo_inclusivo' => true,
            'postulacion_facil' => true,
            'notificar_postulaciones' => true,
            'evaluacion_online' => false,
            'evaluacion_manual' => false,
            'vigencia_dias' => 30,
            'estado' => 'publicada',
        ];

        return [
            [...$base,
                'cargo' => 'Gerente de Operaciones',
                'descripcion' => 'Liderar la operación nacional, optimizar procesos críticos y acompañar a equipos multidisciplinarios en el cumplimiento de objetivos de servicio, calidad y productividad.',
                'modalidad' => 'Híbrida', 'comuna' => 'Concepción',
                'actividad_empresa' => 'Transporte / Logística', 'jerarquia' => 'Gerencia / Dirección',
                'sueldo' => 4200000, 'requisitos' => 'Experiencia liderando operaciones, presupuestos e indicadores. Capacidad para desarrollar equipos y conducir procesos de mejora continua.',
                'experiencia_laboral' => '15 años o más', 'competencias' => ['Liderazgo', 'Mejora continua', 'Planificación estratégica'],
            ],
            [...$base,
                'cargo' => 'Jefe/a de Finanzas',
                'descripcion' => 'Responsable de planificación financiera, control de gestión, presupuesto y reportería ejecutiva para apoyar decisiones estratégicas y asegurar la sostenibilidad del negocio.',
                'modalidad' => 'Presencial', 'comuna' => 'Santiago',
                'actividad_empresa' => 'Banca y servicios financieros', 'jerarquia' => 'Jefatura',
                'sueldo' => 3200000, 'requisitos' => 'Formación profesional afín y experiencia en planificación, presupuesto, tesorería y coordinación de equipos financieros.',
                'experiencia_laboral' => '10 años o más', 'competencias' => ['Control de gestión', 'Finanzas', 'Excel avanzado'],
                'idiomas' => ['Español', 'Inglés'],
            ],
            [...$base,
                'cargo' => 'Consultor/a Senior de Personas',
                'descripcion' => 'Diseñar e implementar proyectos de desarrollo organizacional, gestión del cambio, liderazgo e integración intergeneracional para clientes de distintas industrias.',
                'modalidad' => 'Remota', 'comuna' => 'Nacional',
                'actividad_empresa' => 'Servicios Profesionales (Auditoría / Consultoría / Legales)', 'jerarquia' => 'Profesional / Especialista',
                'sueldo' => 2500000, 'requisitos' => 'Experiencia en consultoría de personas, facilitación de talleres y relacionamiento con clientes corporativos.',
                'experiencia_laboral' => '10 años o más', 'competencias' => ['Consultoría', 'Facilitación', 'Gestión del cambio'],
            ],
            [...$base,
                'cargo' => 'Supervisor/a de Mantención',
                'descripcion' => 'Coordinar planes de mantenimiento preventivo y correctivo, asegurar la continuidad operacional y promover estándares de seguridad y confiabilidad en planta.',
                'modalidad' => 'Presencial', 'comuna' => 'Talcahuano',
                'actividad_empresa' => 'Forestal / Papelera', 'jerarquia' => 'Coordinación / Supervisión',
                'sueldo' => 2300000, 'requisitos' => 'Experiencia en mantenimiento industrial, planificación de recursos, seguridad y coordinación de personal técnico.',
                'experiencia_laboral' => '10 años o más', 'estudios_minimos' => 'CFT / Instituto Profesional',
                'competencias' => ['Mantenimiento industrial', 'Seguridad', 'Planificación'],
            ],
            [...$base,
                'cargo' => 'Especialista en Transformación Digital',
                'descripcion' => 'Impulsar iniciativas de automatización, adopción tecnológica e inteligencia artificial, conectando las necesidades del negocio con soluciones digitales concretas.',
                'modalidad' => 'Híbrida', 'comuna' => 'Santiago',
                'actividad_empresa' => 'Tecnología de la Información', 'jerarquia' => 'Profesional / Especialista',
                'sueldo' => 3000000, 'requisitos' => 'Experiencia implementando proyectos digitales, levantando procesos y acompañando equipos en adopción de nuevas herramientas.',
                'experiencia_laboral' => '5 años o más', 'competencias' => ['Transformación digital', 'Inteligencia artificial', 'Gestión de proyectos'],
                'idiomas' => ['Español', 'Inglés'],
            ],
            [...$base,
                'cargo' => 'Encargado/a de Calidad',
                'descripcion' => 'Administrar el sistema de gestión de calidad, liderar auditorías internas y coordinar acciones correctivas para asegurar el cumplimiento normativo y operacional.',
                'modalidad' => 'Presencial', 'comuna' => 'Los Ángeles',
                'actividad_empresa' => 'Alimentos', 'jerarquia' => 'Jefatura',
                'sueldo' => 2100000, 'requisitos' => 'Experiencia en sistemas de calidad, auditorías, indicadores y mejora de procesos en entornos productivos.',
                'experiencia_laboral' => '5 años o más', 'competencias' => ['Gestión de calidad', 'Auditoría', 'Mejora continua'],
            ],
            [...$base,
                'cargo' => 'Director/a de Proyectos',
                'descripcion' => 'Dirigir una cartera de proyectos de infraestructura, coordinando equipos, proveedores, permisos, presupuesto y relacionamiento con distintos grupos de interés.',
                'modalidad' => 'Híbrida', 'comuna' => 'Viña del Mar',
                'actividad_empresa' => 'Construcción', 'jerarquia' => 'Gerencia / Dirección',
                'sueldo' => 4500000, 'requisitos' => 'Trayectoria en dirección de proyectos complejos, control contractual, planificación y gestión de equipos multidisciplinarios.',
                'experiencia_laboral' => '20 años o más', 'competencias' => ['Dirección de proyectos', 'Negociación', 'Gestión contractual'],
            ],
            [...$base,
                'cargo' => 'Mentor/a de Emprendimientos',
                'tipo_cargo' => 'Consultoría', 'vacantes' => 3,
                'descripcion' => 'Acompañar a personas emprendedoras en estrategia, modelo de negocio, finanzas y desarrollo comercial mediante sesiones individuales y talleres prácticos.',
                'modalidad' => 'Remota', 'comuna' => 'Nacional',
                'actividad_empresa' => 'Educación', 'jerarquia' => 'Profesional / Especialista',
                'sueldo' => null, 'mostrar_sueldo' => false,
                'requisitos' => 'Experiencia gestionando negocios, asesorando organizaciones o liderando áreas comerciales y financieras.',
                'experiencia_laboral' => '15 años o más', 'competencias' => ['Mentoría', 'Estrategia', 'Desarrollo comercial'],
                'vigencia_dias' => 60,
            ],
        ];
    }
}
