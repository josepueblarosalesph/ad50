<?php

namespace Database\Seeders;

use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Publicacion;
use Illuminate\Database\Seeder;

class PostulacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $publicaciones = Publicacion::query()->get();

        Postulante::query()
            ->take($publicaciones->count())
            ->get()
            ->each(function (Postulante $postulante, int $index) use ($publicaciones): void {
                $publicacion = $publicaciones->get($index);

                if ($publicacion !== null) {
                    Postulacion::query()->firstOrCreate(
                        [
                            'publicacion_id' => $publicacion->id,
                            'postulante_id' => $postulante->id,
                        ],
                        [
                            'respuestas' => ['Me interesa aportar mi experiencia al equipo.'],
                            'estado' => 'enviada',
                        ],
                    );
                }
            });
    }
}
