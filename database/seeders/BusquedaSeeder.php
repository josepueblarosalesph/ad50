<?php

namespace Database\Seeders;

use App\Models\Busqueda;
use App\Models\BusquedaCandidato;
use App\Models\Empresa;
use App\Models\Postulante;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Database\Seeder;

class BusquedaSeeder extends Seeder
{
    public function run(MatchingService $matching): void
    {
        $postulantes = Postulante::query()->where('visible', true)->get();

        foreach ($this->busquedas() as $datos) {
            $empresa = Empresa::query()
                ->whereBelongsTo(User::query()->where('email', $datos['empresa_email'])->firstOrFail())
                ->firstOrFail();

            $busqueda = Busqueda::query()->updateOrCreate(
                ['empresa_id' => $empresa->id, 'titulo' => $datos['titulo']],
                [
                    'rubro_oculto' => $datos['rubro_oculto'],
                    'criterios' => $datos['criterios'],
                    'estado' => 'activa',
                ],
            );

            $coincidencias = $postulantes
                ->map(function (Postulante $postulante) use ($busqueda, $matching): ?array {
                    $detalle = $matching->evaluar($postulante, $busqueda->criterios ?? []);

                    if (collect($detalle)->contains(fn (array $criterio): bool => ! $criterio['cumple'])) {
                        return null;
                    }

                    $total = count($detalle);

                    return [
                        'busqueda_id' => $busqueda->id,
                        'postulante_id' => $postulante->id,
                        'match_score' => 100,
                        'criterios_cumplidos' => $total,
                        'criterios_totales' => $total,
                        'criterios_detalle' => json_encode(array_values($detalle), JSON_THROW_ON_ERROR),
                        'estado_match' => 'cumple',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->filter()
                ->values();

            $busqueda->candidatos()
                ->whereNotIn('postulante_id', $coincidencias->pluck('postulante_id'))
                ->delete();

            if ($coincidencias->isNotEmpty()) {
                BusquedaCandidato::query()->upsert(
                    $coincidencias->all(),
                    ['busqueda_id', 'postulante_id'],
                    ['match_score', 'criterios_cumplidos', 'criterios_totales', 'criterios_detalle', 'estado_match', 'updated_at'],
                );
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function busquedas(): array
    {
        return [
            $this->busqueda('rrhh@empresa.cl', 'Liderazgo financiero centro sur', 'Servicios financieros', [
                'cargo' => ['Finanzas'], 'carrera' => ['Ingeniería Civil / Ingeniería Comercial'],
                'especialidad' => 'Finanzas', 'industria' => ['Banca y servicios financieros', 'Forestal / Papelera'],
                'ciudad' => ['Biobío'], 'min_anios' => 15, 'palabra_clave' => ['transformación'],
            ]),
            $this->busqueda('rrhh@empresa.cl', 'Profesionales senior con experiencia comprobada', 'Empresa nacional', [
                'cargo' => [], 'carrera' => [], 'especialidad' => '', 'industria' => [], 'ciudad' => [],
                'min_anios' => 15, 'palabra_clave' => [],
            ]),
            $this->busqueda('rrhh@empresa.cl', 'Sostenibilidad y medio ambiente', 'Energía y recursos naturales', [
                'cargo' => ['Medio Ambiente'], 'carrera' => ['Ingeniería Civil / Ingeniería Comercial'],
                'especialidad' => 'Medio Ambiente', 'industria' => ['Generación de Energía', 'Silvicultura / Forestal'],
                'ciudad' => ['Los Ríos'], 'min_anios' => 10, 'palabra_clave' => ['sostenibilidad'],
            ]),
            $this->busqueda('talento@andesmining.cl', 'Gerencia de operaciones mineras', 'Minería', [
                'cargo' => ['Operaciones'], 'carrera' => ['Ingeniería Civil / Ingeniería Comercial'],
                'especialidad' => 'Operaciones', 'industria' => ['Minería'],
                'ciudad' => ['Metropolitana de Santiago', 'Tarapacá'], 'min_anios' => 20, 'palabra_clave' => ['operacional'],
            ]),
            $this->busqueda('talento@andesmining.cl', 'Logística y abastecimiento zona norte', 'Servicios industriales', [
                'cargo' => ['Logística / Cadena de suministros'], 'carrera' => ['Ingeniería Civil / Ingeniería Comercial'],
                'especialidad' => 'Logística / Cadena de suministros', 'industria' => ['Transporte / Logística', 'Minería'],
                'ciudad' => ['Antofagasta'], 'min_anios' => 15, 'palabra_clave' => ['suministro'],
            ]),
            $this->busqueda('personas@saludsur.cl', 'Dirección y calidad clínica', 'Salud', [
                'cargo' => ['Salud'], 'carrera' => ['Médico'], 'especialidad' => 'Gestión de salud',
                'industria' => ['Salud'], 'ciudad' => ['Biobío'], 'min_anios' => 15, 'palabra_clave' => ['calidad'],
            ]),
            $this->busqueda('personas@saludsur.cl', 'Gestión de personas y cultura', 'Servicios', [
                'cargo' => ['Recursos Humanos'], 'carrera' => ['Psicólogo'], 'especialidad' => 'Organizacional / Trabajo',
                'industria' => ['Servicios Profesionales (Auditoría / Consultoría / Legales)'],
                'ciudad' => ['Valparaíso'], 'min_anios' => 10, 'palabra_clave' => ['cultura'],
            ]),
            $this->busqueda('seleccion@novatech.cl', 'Transformación digital y datos', 'Tecnología', [
                'cargo' => ['Tecnología / Transformación digital'], 'carrera' => ['Ingeniería Civil / Ingeniería Comercial'],
                'especialidad' => 'Innovación / Transformación digital', 'industria' => ['Tecnología de la Información'],
                'ciudad' => ['Metropolitana de Santiago'], 'min_anios' => 15, 'palabra_clave' => ['digital'],
            ]),
            $this->busqueda('seleccion@novatech.cl', 'Consultoría de cambio organizacional', 'Consultoría', [
                'cargo' => ['Consultoría'], 'carrera' => ['Psicólogo', 'Periodista'], 'especialidad' => '',
                'industria' => ['Servicios Profesionales (Auditoría / Consultoría / Legales)'],
                'ciudad' => ['Metropolitana de Santiago', 'Biobío'], 'min_anios' => 10, 'palabra_clave' => ['transformación'],
            ]),
            $this->busqueda('capitalhumano@logisticapacifico.cl', 'Jefaturas y gerencias de mantenimiento', 'Industria de alimentos', [
                'cargo' => ['Mantención'], 'carrera' => ['Ingeniería Civil / Ingeniería Comercial'],
                'especialidad' => 'Mantención', 'industria' => ['Alimentos'], 'ciudad' => ['Maule'],
                'min_anios' => 20, 'palabra_clave' => ['mantenimiento'],
            ]),
            $this->busqueda('capitalhumano@logisticapacifico.cl', 'Talento senior disponible en regiones', 'Empresa nacional', [
                'cargo' => [], 'carrera' => [], 'especialidad' => '', 'industria' => [],
                'ciudad' => ['Biobío', 'Los Lagos', 'La Araucanía', 'Los Ríos'],
                'min_anios' => 15, 'palabra_clave' => [],
            ]),
        ];
    }

    /** @param array<string, mixed> $criterios
     * @return array<string, mixed>
     */
    private function busqueda(string $empresaEmail, string $titulo, string $rubroOculto, array $criterios): array
    {
        return [
            'empresa_email' => $empresaEmail,
            'titulo' => $titulo,
            'rubro_oculto' => $rubroOculto,
            'criterios' => $criterios,
        ];
    }
}
