<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publicacion>
 */
class PublicacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => function (): int {
                $user = User::factory()->create(['role' => 'empresa']);

                return Empresa::query()->create([
                    'user_id' => $user->id,
                    'razon_social' => fake()->company(),
                    'estado_activacion' => 'activa',
                ])->id;
            },
            'cargo' => fake()->jobTitle(),
            'tipo_cargo' => 'Jornada completa',
            'vacantes' => fake()->numberBetween(1, 3),
            'nombre_empresa' => fake()->company(),
            'descripcion' => fake()->paragraphs(4, true),
            'modalidad' => fake()->randomElement(['Presencial', 'Híbrida', 'Remota']),
            'pais' => 'Chile',
            'comuna' => fake()->randomElement(['Concepción', 'Santiago', 'Valparaíso']),
            'actividad_empresa' => 'Servicios Profesionales (Auditoría / Consultoría / Legales)',
            'jerarquia' => 'Profesional / Especialista',
            'sueldo' => fake()->numberBetween(10, 40) * 100000,
            'mostrar_sueldo' => true,
            'requisitos' => fake()->paragraphs(2, true),
            'experiencia_laboral' => '5 años o más',
            'estudios_minimos' => 'Título Profesional',
            'situacion_academica' => 'Titulado/a',
            'competencias' => ['Liderazgo', 'Gestión de proyectos'],
            'idiomas' => ['Español'],
            'preguntas' => ['¿Por qué te interesa esta oportunidad?'],
            'empleo_inclusivo' => false,
            'postulacion_facil' => true,
            'notificar_postulaciones' => true,
            'evaluacion_online' => false,
            'evaluacion_manual' => false,
            'vigencia_dias' => 30,
            'vigente_hasta' => today()->addDays(30),
            'estado' => 'publicada',
        ];
    }
}
