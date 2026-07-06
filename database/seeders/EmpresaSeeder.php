<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = $this->empresas();
        $ahora = now();
        $password = Hash::make('password');
        $planes = Plan::query()->where('audiencia', 'empresa')->pluck('id', 'codigo');

        User::query()->upsert(
            collect($empresas)->map(fn (array $empresa): array => [
                'name' => $empresa['contacto_principal_nombre'],
                'email' => $empresa['email'],
                'email_verified_at' => $ahora,
                'password' => $password,
                'role' => 'empresa',
                'acepta_ley_21719' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->all(),
            ['email'],
            ['name', 'email_verified_at', 'role', 'acepta_ley_21719', 'updated_at'],
        );

        $usuarios = User::query()->whereIn('email', collect($empresas)->pluck('email'))->pluck('id', 'email');
        $empresasExistentes = Empresa::query()->whereIn('user_id', $usuarios)->pluck('id', 'user_id');
        $siguienteId = (Empresa::query()->max('id') ?? 0) + 1;
        $registros = collect($empresas)->map(
            function (array $empresa) use ($usuarios, $empresasExistentes, $planes, $ahora, &$siguienteId): array {
                $userId = $usuarios[$empresa['email']];

                return [
                    'id' => $empresasExistentes[$userId] ?? $siguienteId++,
                    'user_id' => $userId,
                    'razon_social' => $empresa['razon_social'],
                    'rut' => $empresa['rut'],
                    'rubro' => $empresa['rubro'],
                    'estado_activacion' => 'activa',
                    'contacto_principal_nombre' => $empresa['contacto_principal_nombre'],
                    'contacto_principal_cargo' => $empresa['contacto_principal_cargo'],
                    'contacto_principal_email' => $empresa['email'],
                    'contacto_principal_telefono' => $empresa['telefono'],
                    'contacto_tecnico_nombre' => $empresa['contacto_tecnico_nombre'],
                    'contacto_tecnico_email' => $empresa['contacto_tecnico_email'],
                    'contacto_tecnico_telefono' => $empresa['telefono_tecnico'],
                    'datos_enviados_at' => $ahora->copy()->subMonths(2),
                    'activada_at' => $ahora->copy()->subMonths(2),
                    'plan_id' => $planes[$empresa['plan']],
                    'plan_hasta' => $ahora->copy()->addYear()->toDateString(),
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            },
        )->all();

        Empresa::query()->upsert(
            $registros,
            ['id'],
            array_values(array_diff(array_keys($registros[0]), ['id', 'user_id', 'created_at'])),
        );
    }

    /** @return list<array<string, string>> */
    private function empresas(): array
    {
        return [
            [
                'email' => 'rrhh@empresa.cl', 'razon_social' => 'Forestal del Bío Bío S.A.', 'rut' => '76.123.456-7',
                'rubro' => 'Forestal / Papelera', 'plan' => 'empresa_pro', 'contacto_principal_nombre' => 'Carolina Reyes',
                'contacto_principal_cargo' => 'Gerenta de Personas', 'telefono' => '+56 9 7000 1001',
                'contacto_tecnico_nombre' => 'Felipe Soto', 'contacto_tecnico_email' => 'soporte@empresa.cl', 'telefono_tecnico' => '+56 9 7100 1001',
            ],
            [
                'email' => 'talento@andesmining.cl', 'razon_social' => 'Andes Mining Services SpA', 'rut' => '77.234.567-8',
                'rubro' => 'Minería', 'plan' => 'empresa_pro', 'contacto_principal_nombre' => 'Valentina Morales',
                'contacto_principal_cargo' => 'Directora de Talento', 'telefono' => '+56 9 7000 1002',
                'contacto_tecnico_nombre' => 'Tomás Riquelme', 'contacto_tecnico_email' => 'ti@andesmining.cl', 'telefono_tecnico' => '+56 9 7100 1002',
            ],
            [
                'email' => 'personas@saludsur.cl', 'razon_social' => 'Red Salud Sur Ltda.', 'rut' => '76.345.678-9',
                'rubro' => 'Salud', 'plan' => 'empresa_basic', 'contacto_principal_nombre' => 'Camila Torres',
                'contacto_principal_cargo' => 'Subgerenta de Personas', 'telefono' => '+56 9 7000 1003',
                'contacto_tecnico_nombre' => 'Nicolás Vera', 'contacto_tecnico_email' => 'sistemas@saludsur.cl', 'telefono_tecnico' => '+56 9 7100 1003',
            ],
            [
                'email' => 'seleccion@novatech.cl', 'razon_social' => 'NovaTech Chile S.A.', 'rut' => '77.456.789-0',
                'rubro' => 'Tecnología de la Información', 'plan' => 'empresa_pro', 'contacto_principal_nombre' => 'Fernanda Silva',
                'contacto_principal_cargo' => 'People Lead', 'telefono' => '+56 9 7000 1004',
                'contacto_tecnico_nombre' => 'Diego Ramírez', 'contacto_tecnico_email' => 'plataforma@novatech.cl', 'telefono_tecnico' => '+56 9 7100 1004',
            ],
            [
                'email' => 'capitalhumano@logisticapacifico.cl', 'razon_social' => 'Logística Pacífico S.A.', 'rut' => '76.567.890-1',
                'rubro' => 'Transporte / Logística', 'plan' => 'empresa_basic', 'contacto_principal_nombre' => 'Pablo Contreras',
                'contacto_principal_cargo' => 'Jefe de Capital Humano', 'telefono' => '+56 9 7000 1005',
                'contacto_tecnico_nombre' => 'Constanza Leiva', 'contacto_tecnico_email' => 'tecnologia@logisticapacifico.cl', 'telefono_tecnico' => '+56 9 7100 1005',
            ],
        ];
    }
}
