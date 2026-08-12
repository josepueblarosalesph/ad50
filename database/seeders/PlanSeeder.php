<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->planes() as $plan) {
            Plan::query()->updateOrCreate(['codigo' => $plan['codigo']], $plan);
        }
    }

    /** @return list<array<string, mixed>> */
    private function planes(): array
    {
        return [
            [
                'codigo' => 'empresa_basic',
                'nombre' => 'Básico',
                'audiencia' => 'empresa',
                'precio_clp' => 0,
                'precio_uf' => 5,
                'desbloqueos' => 10,
                'publicaciones' => 5,
                'periodo' => 'anual',
                // Se cobra una vez y no se renueva solo, pero da acceso durante un año.
                // Cada empresa puede contratarlo hasta 3 veces en 12 meses, y los cupos
                // de cada compra se suman.
                'pago_unico' => true,
                'max_contrataciones_anuales' => 3,
                'destacado' => false,
                'features' => ['5 publicaciones', 'Match inteligente', '10 desbloqueos de perfiles', 'Pago único · hasta 3 al año'],
                'recomendacion' => 'Recomendado para búsquedas puntuales',
            ],
            [
                'codigo' => 'empresa_pro',
                'nombre' => 'Profesional',
                'audiencia' => 'empresa',
                'precio_clp' => 0,
                'precio_uf' => 30,
                'desbloqueos' => 50,
                'publicaciones' => 30,
                'periodo' => 'anual',
                'destacado' => false,
                'features' => ['30 publicaciones', 'Match inteligente', '50 desbloqueos de perfiles', 'Soporte técnico'],
                'recomendacion' => 'Recomendado para múltiples búsquedas',
            ],
            [
                'codigo' => 'empresa_premium',
                'nombre' => 'Premium',
                'audiencia' => 'empresa',
                'precio_clp' => 0,
                'precio_uf' => 45,
                'desbloqueos' => 100,
                'publicaciones' => null, // Ilimitadas.
                'periodo' => 'anual',
                'destacado' => true,
                'features' => ['Publicaciones ilimitadas', 'Match inteligente', '100 desbloqueos de perfiles', 'Soporte técnico'],
                'recomendacion' => 'Recomendado para empresas con alto volumen de publicaciones.',
            ],
        ];
    }
}
