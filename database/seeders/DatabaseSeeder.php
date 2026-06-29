<?php

namespace Database\Seeders;

use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Plan;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Planes
        $planPost = Plan::create([
            'codigo' => 'postulante', 'nombre' => 'Postulante visible',
            'audiencia' => 'postulante', 'precio_clp' => 9900, 'periodo' => 'anual',
            'features' => ['Ficha siempre visible', 'Acceso a tus matches', 'Soporte por email'],
        ]);
        Plan::create([
            'codigo' => 'empresa_basic', 'nombre' => 'Empresa · Básico',
            'audiencia' => 'empresa', 'precio_clp' => 89000,
            'features' => ['1 búsqueda activa', '10 contactos por mes', 'Filtros estándar'],
        ]);
        $planPro = Plan::create([
            'codigo' => 'empresa_pro', 'nombre' => 'Empresa · Pro',
            'audiencia' => 'empresa', 'precio_clp' => 189000, 'destacado' => true,
            'features' => ['Búsquedas ilimitadas', 'Contactos ilimitados', 'Filtros avanzados', 'Soporte dedicado'],
        ]);

        // Postulante demo
        $maria = User::create([
            'name' => 'María José Fuentes',
            'email' => 'maria@adconsulting.cl',
            'password' => Hash::make('password'),
            'role' => 'postulante',
            'acepta_ley_21719' => true,
        ]);
        $mariaP = Postulante::create([
            'user_id' => $maria->id,
            'telefono' => '+56 9 5555 1234',
            'ciudad' => 'Concepción',
            'cargo_actual' => 'Subgerente de Finanzas',
            'industria' => 'Forestal',
            'anios_experiencia' => 18,
            'completitud' => 72,
            'visible' => true,
            'suscripcion_hasta' => now()->addMonths(8),
        ]);

        // Empresa demo + búsquedas
        $rrhh = User::create([
            'name' => 'Carolina Reyes',
            'email' => 'rrhh@empresa.cl',
            'password' => Hash::make('password'),
            'role' => 'empresa',
        ]);
        $emp = Empresa::create([
            'user_id' => $rrhh->id,
            'razon_social' => 'Forestal del Bío Bío S.A.',
            'rubro' => 'Forestal',
            'plan_id' => $planPro->id,
            'plan_hasta' => now()->addYear(),
        ]);

        foreach ([
            ['Subgerente/a de Finanzas — rubro Forestal', 3, 3, 'cumple', now()->subDays(2)],
            ['Gerente de Administración — Banca',          3, 3, 'cumple', now()->subDays(6)],
            ['Controller Senior — Manufactura',            2, 3, 'parcial', now()->subWeek()],
            ['Jefatura Contable — Retail',                 3, 3, 'cumple', now()->subDays(11)],
            ['Gerente Financiero — Salud',                 3, 3, 'cumple', now()->subDays(14)],
        ] as [$titulo, $cum, $tot, $estado, $when]) {
            $b = Busqueda::create([
                'empresa_id' => $emp->id,
                'titulo' => $titulo,
                'rubro_oculto' => explode(' — ', $titulo)[1] ?? 'Empresa',
                'criterios' => ['cargo' => $titulo, 'min_anios' => 10],
            ]);
            BusquedaCandidato::create([
                'busqueda_id' => $b->id,
                'postulante_id' => $mariaP->id,
                'criterios_cumplidos' => $cum,
                'criterios_totales' => $tot,
                'estado_match' => $estado,
                'match_score' => intval($cum / max($tot, 1) * 100),
                'created_at' => $when, 'updated_at' => $when,
            ]);
        }
    }
}
