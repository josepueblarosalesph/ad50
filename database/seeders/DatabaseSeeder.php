<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SuperadminSeeder::class,
            PlanSeeder::class,
            PostulanteSeeder::class,
            EmpresaSeeder::class,
            BusquedaSeeder::class,
            PublicacionSeeder::class,
            PostulacionSeeder::class,
        ]);
    }
}
