<?php

namespace Database\Factories;

use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Postulacion>
 */
class PostulacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'publicacion_id' => Publicacion::factory(),
            'postulante_id' => function (): int {
                $user = User::factory()->create(['role' => 'postulante']);

                return Postulante::factory()->create(['user_id' => $user->id])->id;
            },
            'respuestas' => ['Me interesa aportar mi experiencia al equipo.'],
            'estado' => 'recibida',
        ];
    }
}
