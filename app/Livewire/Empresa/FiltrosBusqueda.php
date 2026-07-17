<?php

namespace App\Livewire\Empresa;

use App\Concerns\FiltraPorEdad;
use App\Concerns\FiltraPorExperiencia;
use App\Models\Busqueda;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FiltrosBusqueda extends Component
{
    use FiltraPorEdad;
    use FiltraPorExperiencia;

    public Busqueda $busqueda;

    /** @var list<string> */
    public array $cargo = [];

    /** @var list<string> */
    public array $carrera = [];

    public string $especialidad = '';

    /** @var list<string> */
    public array $industria = [];

    /** @var list<string> */
    public array $ciudad = [];

    /** @var list<string> */
    public array $habilidad = [];

    /** @var list<string> */
    public array $situacionLaboral = [];

    /** @var list<string> */
    public array $genero = [];

    /** @var list<string> */
    public array $nivelEstudios = [];

    /** @var list<string> */
    public array $situacionEstudios = [];

    /** @var list<string> */
    public array $idioma = [];

    /** @var list<string> */
    public array $actividadEconomica = [];

    public int $rentaMax = 0;

    public string $institucion = '';

    public string $empresa = '';

    /** @var list<string> */
    public array $palabrasClave = [];

    public string $nuevaPalabraClave = '';

    public function mount(Busqueda $busqueda): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->busqueda = $busqueda;
        $criterios = $busqueda->criterios ?? [];
        $this->cargo = $this->normalizarSeleccion($criterios['cargo'] ?? []);
        $this->carrera = $this->normalizarSeleccion($criterios['carrera'] ?? []);
        $this->especialidad = is_array($criterios['especialidad'] ?? '') ? (string) ($criterios['especialidad'][0] ?? '') : (string) ($criterios['especialidad'] ?? '');
        $this->industria = $this->normalizarSeleccion($criterios['industria'] ?? []);
        $this->ciudad = $this->normalizarSeleccion($criterios['ciudad'] ?? []);
        $this->habilidad = $this->normalizarSeleccion($criterios['habilidad'] ?? []);
        $this->situacionLaboral = $this->normalizarSeleccion($criterios['situacion_laboral'] ?? []);
        $this->genero = $this->normalizarSeleccion($criterios['genero'] ?? []);
        $this->nivelEstudios = $this->normalizarSeleccion($criterios['nivel_estudios'] ?? []);
        $this->situacionEstudios = $this->normalizarSeleccion($criterios['situacion_estudios'] ?? []);
        $this->idioma = $this->normalizarSeleccion($criterios['idioma'] ?? []);
        $this->actividadEconomica = $this->normalizarSeleccion($criterios['actividad_economica'] ?? []);
        $this->rentaMax = (int) ($criterios['renta_max'] ?? 0);
        $this->institucion = $criterios['institucion'] ?? '';
        $this->empresa = $criterios['empresa'] ?? '';
        $this->hidratarExperiencia($criterios);
        $this->palabrasClave = $this->normalizarSeleccion($criterios['palabra_clave'] ?? []);
        $this->hidratarEdad($criterios);
    }

    /**
     * Cada criterio se aplica apenas cambia; el texto en edición todavía no es un criterio.
     */
    public function updated(string $propiedad): void
    {
        if ($propiedad === 'nuevaPalabraClave') {
            return;
        }

        $this->guardar(app(MatchingService::class));
    }

    public function agregarPalabraClave(): void
    {
        $palabra = trim($this->nuevaPalabraClave);

        if ($palabra === '' || count($this->palabrasClave) >= 10 || in_array($palabra, $this->palabrasClave, true)) {
            $this->nuevaPalabraClave = '';

            return;
        }

        $this->palabrasClave[] = mb_substr($palabra, 0, 100);
        $this->nuevaPalabraClave = '';
        $this->guardar(app(MatchingService::class));
    }

    public function quitarPalabraClave(int $index): void
    {
        unset($this->palabrasClave[$index]);
        $this->palabrasClave = array_values($this->palabrasClave);
        $this->guardar(app(MatchingService::class));
    }

    public function guardar(MatchingService $matching): void
    {
        $validated = $this->validate([
            'cargo' => ['array'],
            'cargo.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::cargos())],
            'carrera' => ['array'],
            'carrera.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::carrerasEstudio())],
            'especialidad' => ['nullable', 'string', 'max:180'],
            'industria' => ['array'],
            'industria.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::industrias())],
            'ciudad' => ['array'],
            'ciudad.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::regionesInteres())],
            'habilidad' => ['array'],
            'habilidad.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::habilidades())],
            'situacionLaboral' => ['array'],
            'situacionLaboral.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::situacionesLaborales())],
            'genero' => ['array'],
            'genero.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::generos())],
            'nivelEstudios' => ['array'],
            'nivelEstudios.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::nivelesEstudio())],
            'situacionEstudios' => ['array'],
            'situacionEstudios.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::situacionesEstudio())],
            'idioma' => ['array'],
            'idioma.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::idiomasConNivel())],
            'actividadEconomica' => ['array'],
            'actividadEconomica.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::industrias())],
            'rentaMax' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'institucion' => ['nullable', 'string', 'max:180'],
            'empresa' => ['nullable', 'string', 'max:180'],
            'palabrasClave' => ['array', 'max:10'],
            'palabrasClave.*' => ['string', 'max:100', 'distinct'],
            ...$this->reglasExperiencia(),
            ...$this->reglasEdad(),
        ]);

        DB::transaction(function () use ($validated, $matching): void {
            $this->busqueda->update([
                'rubro_oculto' => $validated['industria'][0] ?? null,
                'criterios' => [
                    'cargo' => $validated['cargo'],
                    'carrera' => $validated['carrera'],
                    'especialidad' => $validated['especialidad'],
                    'industria' => $validated['industria'],
                    'ciudad' => $validated['ciudad'],
                    'habilidad' => $validated['habilidad'],
                    'situacion_laboral' => $validated['situacionLaboral'],
                    'genero' => $validated['genero'],
                    'nivel_estudios' => $validated['nivelEstudios'],
                    'situacion_estudios' => $validated['situacionEstudios'],
                    'idioma' => $validated['idioma'],
                    'actividad_economica' => $validated['actividadEconomica'],
                    'renta_max' => (int) ($validated['rentaMax'] ?? 0),
                    'institucion' => $validated['institucion'],
                    'empresa' => $validated['empresa'],
                    'experiencia' => $this->criterioExperiencia($validated['expMin'], $validated['expMax']),
                    'palabra_clave' => $validated['palabrasClave'],
                    'edad' => $this->criterioEdad($validated['edadMin'], $validated['edadMax']),
                ],
            ]);

            $matching->sincronizar($this->busqueda->fresh());
        });

        $this->dispatch('criterios-actualizados');
    }

    public function render(): View
    {
        return view('livewire.empresa.filtros-busqueda', [
            'instituciones' => CatalogosProfesionales::instituciones(),
            'empresas' => CatalogosProfesionales::empresas(),
            'limitesEdad' => CatalogosProfesionales::rangoEdad(),
            'limitesExperiencia' => CatalogosProfesionales::rangoExperiencia(),
            'grupos' => [
                ['Cargo', 'cargo', 'cargo'],
                ['Carrera', 'carrera', 'carrera'],
                ['Industria', 'industria', 'industria'],
                ['Región', 'ciudad', 'ciudad'],
                ['Habilidades', 'habilidad', 'habilidad'],
                ['Situación laboral', 'situacionLaboral', 'situacion_laboral'],
                ['Género', 'genero', 'genero'],
                ['Nivel de estudios', 'nivelEstudios', 'nivel_estudios'],
                ['Situación de estudios', 'situacionEstudios', 'situacion_estudios'],
                ['Idioma', 'idioma', 'idioma'],
                ['Actividad económica', 'actividadEconomica', 'actividad_economica'],
            ],
        ]);
    }

    /** @return list<string> */
    private function normalizarSeleccion(mixed $valor): array
    {
        return collect((array) $valor)->filter(fn (mixed $item): bool => is_string($item) && filled($item))->values()->all();
    }
}
