<?php

namespace Database\Factories;

use App\Models\Postulante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Postulante>
 */
class PostulanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telefono' => $this->faker->phoneNumber(),
            'ciudad' => $this->faker->randomElement(['Santiago', 'Valparaíso', 'Concepción', 'Valdivia']),
            'cargo_actual' => $this->faker->randomElement(['Ingeniero de Software', 'Contador', 'Abogado', 'Ejecutivo de Ventas']),
            'industria' => $this->faker->randomElement(['Tecnología', 'Finanzas', 'Retail', 'Salud']),
            'anios_experiencia' => $this->faker->randomDigit(),
            'completitud' => 100,
            'visible' => true,
        ];
    }
}
