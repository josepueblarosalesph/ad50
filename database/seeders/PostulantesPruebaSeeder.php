<?php

namespace Database\Seeders;

use App\Models\Busqueda;
use App\Models\Postulante;
use App\Models\User;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Genera un volumen grande de postulantes de prueba (por defecto 300) con carreras,
 * ciudades, industrias, idiomas y habilidades variadas, para medir rendimiento.
 *
 * Es idempotente y NO destructivo con los datos reales: sus postulantes se marcan con
 * el email "@ad50pruebas.test" y al re-ejecutar sólo borra su propio lote anterior,
 * dejando intactos todos los demás postulantes.
 */
class PostulantesPruebaSeeder extends Seeder
{
    private const DOMINIO = 'ad50pruebas.test';

    private const CANTIDAD = 300;

    /** @var list<string> */
    private const NOMBRES = [
        'Carlos', 'María', 'José', 'Ana', 'Luis', 'Carmen', 'Jorge', 'Patricia', 'Manuel', 'Rosa',
        'Francisco', 'Isabel', 'Roberto', 'Marcela', 'Sergio', 'Gloria', 'Eduardo', 'Verónica', 'Ricardo', 'Sandra',
        'Fernando', 'Claudia', 'Andrés', 'Paula', 'Rodrigo', 'Cecilia', 'Álvaro', 'Mónica', 'Gonzalo', 'Teresa',
    ];

    /** @var list<string> */
    private const APELLIDOS = [
        'González', 'Muñoz', 'Rojas', 'Díaz', 'Pérez', 'Soto', 'Contreras', 'Silva', 'Martínez', 'Sepúlveda',
        'Morales', 'Rodríguez', 'López', 'Fuentes', 'Hernández', 'Torres', 'Araya', 'Flores', 'Espinoza', 'Valenzuela',
        'Castillo', 'Tapia', 'Reyes', 'Gutiérrez', 'Castro', 'Vargas', 'Álvarez', 'Vásquez', 'Sánchez', 'Fernández',
    ];

    public function run(): void
    {
        $matching = app(MatchingService::class);

        // 1) Borrar solo el lote de prueba anterior (cascade elimina sus postulantes/matches).
        User::query()->where('email', 'like', '%@'.self::DOMINIO)->delete();

        // 2) Catálogos para variar los datos.
        $carreras = CatalogosProfesionales::carrerasEstudio();
        $regiones = CatalogosProfesionales::regiones();
        $industrias = CatalogosProfesionales::industrias();
        $cargos = CatalogosProfesionales::cargos();
        $habilidades = CatalogosProfesionales::habilidades();
        $instituciones = CatalogosProfesionales::instituciones();
        $empresas = CatalogosProfesionales::empresas();
        $niveles = CatalogosProfesionales::nivelesEstudio();
        $situacionesEstudio = CatalogosProfesionales::situacionesEstudio();
        $situacionesLaborales = CatalogosProfesionales::situacionesLaborales();
        $generos = CatalogosProfesionales::generos();
        $idiomas = CatalogosProfesionales::idiomas();
        $nivelesIdioma = CatalogosProfesionales::nivelesIdioma();
        $modalidades = CatalogosProfesionales::modalidadesTrabajoPreferidas();

        $ahora = now();
        $password = Hash::make('password');
        $anioActual = (int) $ahora->year;

        // 3) Crear usuarios.
        $usuarios = collect(range(0, self::CANTIDAD - 1))->map(function (int $i) use ($ahora, $password): array {
            [$nombre, $apellido] = $this->nombreCompleto($i);

            return [
                'name' => $nombre.' '.$apellido,
                'nombres' => $nombre,
                'apellidos' => $apellido,
                'email' => 'prueba'.($i + 1).'@'.self::DOMINIO,
                'email_verified_at' => $ahora,
                'password' => $password,
                'role' => 'postulante',
                'acepta_ley_21719' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        });

        User::query()->insert($usuarios->all());

        $idsPorEmail = User::query()
            ->where('email', 'like', '%@'.self::DOMINIO)
            ->pluck('id', 'email');

        // 4) Crear postulantes con datos variados.
        $postulantes = collect(range(0, self::CANTIDAD - 1))->map(function (int $i) use (
            $idsPorEmail, $ahora, $anioActual, $carreras, $regiones, $industrias, $cargos,
            $habilidades, $instituciones, $empresas, $niveles, $situacionesEstudio,
            $situacionesLaborales, $generos, $idiomas, $nivelesIdioma, $modalidades
        ): array {
            $carrera = $carreras[$i % count($carreras)];
            $region = $regiones[$i % count($regiones)];
            $industria = $industrias[$i % count($industrias)];
            $industria2 = $industrias[($i + 7) % count($industrias)];
            $cargo = $cargos[($i * 137) % count($cargos)];
            $empresa = $empresas[($i * 53) % count($empresas)];
            $institucion = $instituciones[$i % count($instituciones)];
            $nivel = $niveles[$i % count($niveles)];
            $edad = 50 + ($i % 21);
            $anios = 15 + ($i % 25);
            $inicioAnio = $anioActual - $anios;

            $habilidadesPostulante = [
                $habilidades[($i * 3) % count($habilidades)],
                $habilidades[($i * 3 + 1) % count($habilidades)],
                $habilidades[($i * 3 + 2) % count($habilidades)],
            ];

            $regionesInteres = array_values(array_unique([
                $region,
                $i % 4 === 0 ? 'Nacional' : $regiones[($i + 5) % count($regiones)],
            ]));

            $idiomasPostulante = [
                ['idioma' => 'Español', 'nivel' => 'Avanzado'],
                ['idioma' => $idiomas[$i % count($idiomas)], 'nivel' => $nivelesIdioma[$i % count($nivelesIdioma)]],
            ];

            $educaciones = [[
                'nivel' => $nivel,
                'pais' => 'Chile',
                'institucion' => $institucion,
                'carrera' => $carrera,
                'mencion' => '',
                'modalidad' => 'Presencial',
                'situacion' => $situacionesEstudio[$i % count($situacionesEstudio)],
                'inicio_anio' => $inicioAnio - 5,
                'termino_anio' => $inicioAnio - 1,
                'egreso_anio' => null,
            ]];

            $experiencias = [[
                'cargo' => $cargo,
                'cargo_otro' => '',
                'tipo_trabajo' => 'Jornada completa',
                'empresa' => $empresa,
                'empresa_otro' => '',
                'jerarquia' => 'Jefatura',
                'actividad_empresa' => $industria,
                'inicio_mes' => 3,
                'inicio_anio' => $inicioAnio,
                'actualmente' => true,
                'fin_mes' => null,
                'fin_anio' => null,
                'responsabilidades' => 'Responsable de '.$cargo.' en el rubro '.$industria.'.',
            ]];

            $atributos = [
                'user_id' => $idsPorEmail['prueba'.($i + 1).'@'.self::DOMINIO],
                'rut' => sprintf('2%d.%03d.%03d-%d', ($i % 8) + 1, 100 + ($i % 900), 200 + ($i % 700), $i % 10),
                'anio_nacimiento' => $anioActual - $edad,
                'genero' => $generos[$i % count($generos)],
                'titular' => Str::limit($cargo, 70, '').' · '.$anios.' años exp.',
                'telefono' => '+56 9 '.str_pad((string) (70000000 + $i), 8, '0', STR_PAD_LEFT),
                'linkedin' => 'https://linkedin.com/in/prueba-'.($i + 1),
                'ciudad' => $region,
                'regiones_interes' => json_encode($regionesInteres, JSON_THROW_ON_ERROR),
                'industrias_interes' => json_encode(array_values(array_unique([$industria, $industria2])), JSON_THROW_ON_ERROR),
                'modalidad_trabajo' => json_encode([$modalidades[$i % count($modalidades)]], JSON_THROW_ON_ERROR),
                'habilidades' => json_encode($habilidadesPostulante, JSON_THROW_ON_ERROR),
                'cargo_actual' => $cargo,
                'empresa_actual' => $empresa,
                'carrera' => $carrera,
                'universidad' => $institucion,
                'especialidad' => '',
                'situacion_laboral' => $situacionesLaborales[$i % count($situacionesLaborales)],
                'expectativa_renta' => 1000000 + ($i % 40) * 250000,
                'educaciones' => json_encode($educaciones, JSON_THROW_ON_ERROR),
                'idiomas' => json_encode($idiomasPostulante, JSON_THROW_ON_ERROR),
                'experiencias' => json_encode($experiencias, JSON_THROW_ON_ERROR),
                'resumen_profesional' => 'Profesional de '.$carrera.' con '.$anios.' años de experiencia en '.$industria.', radicado en '.$region.'.',
                'anios_experiencia' => $anios,
                'completitud' => 100,
                'visible' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];

            return $atributos;
        });

        // Insertar en tandas para no armar una sola query gigante.
        foreach ($postulantes->chunk(100) as $tanda) {
            Postulante::query()->insert($tanda->all());
        }

        // 5) Recalcular el matching de los procesos vigentes contra la nueva base.
        Busqueda::query()
            ->whereIn('estado', Busqueda::ESTADOS_ACTIVOS)
            ->each(fn (Busqueda $busqueda) => $matching->sincronizar($busqueda));

        $this->command?->info('Creados '.self::CANTIDAD.' postulantes de prueba (@'.self::DOMINIO.').');
    }

    /** @return array{0: string, 1: string} */
    private function nombreCompleto(int $i): array
    {
        $nombre = self::NOMBRES[$i % count(self::NOMBRES)];
        $apellido = self::APELLIDOS[intdiv($i, count(self::NOMBRES)) % count(self::APELLIDOS)];

        return [$nombre, $apellido];
    }
}
