<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FiltrosBusqueda extends Component
{
    public Busqueda $busqueda;

    /** @var list<string> */
    public array $cargo = [];

    /** @var list<string> */
    public array $carrera = [];

    /** @var list<string> */
    public array $especialidad = [];

    /** @var list<string> */
    public array $industria = [];

    /** @var list<string> */
    public array $ciudad = [];

    public string $institucion = '';

    public string $empresa = '';

    public int $aniosMinimos = 0;

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
        $this->especialidad = $this->normalizarSeleccion($criterios['especialidad'] ?? []);
        $this->industria = $this->normalizarSeleccion($criterios['industria'] ?? []);
        $this->ciudad = $this->normalizarSeleccion($criterios['ciudad'] ?? []);
        $this->institucion = $criterios['institucion'] ?? '';
        $this->empresa = $criterios['empresa'] ?? '';
        $this->aniosMinimos = (int) ($criterios['min_anios'] ?? 0);
        $this->palabrasClave = $this->normalizarSeleccion($criterios['palabra_clave'] ?? []);
    }

    /**
     * Cada criterio se aplica apenas cambia; el texto en edición todavía no es un criterio.
     */
    public function updated(string $propiedad): void
    {
        if ($propiedad === 'nuevaPalabraClave') {
            return;
        }

        if (str_starts_with($propiedad, 'carrera')) {
            $this->especialidad = array_values(array_intersect($this->especialidad, $this->especialidadesDisponibles()));
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
            'cargo.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::cargosAreas())],
            'carrera' => ['array'],
            'carrera.*' => ['string', 'distinct', Rule::in(array_keys(CatalogosProfesionales::carreras()))],
            'especialidad' => ['array'],
            'especialidad.*' => ['string', 'distinct', Rule::in($this->especialidadesDisponibles())],
            'industria' => ['array'],
            'industria.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::industrias())],
            'ciudad' => ['array'],
            'ciudad.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::regiones())],
            'institucion' => ['nullable', 'string', 'max:180'],
            'empresa' => ['nullable', 'string', 'max:180'],
            'aniosMinimos' => ['required', 'integer', Rule::in(array_keys(CatalogosProfesionales::rangosExperiencia()))],
            'palabrasClave' => ['array', 'max:10'],
            'palabrasClave.*' => ['string', 'max:100', 'distinct'],
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
                    'institucion' => $validated['institucion'],
                    'empresa' => $validated['empresa'],
                    'min_anios' => $validated['aniosMinimos'],
                    'palabra_clave' => $validated['palabrasClave'],
                ],
            ]);

            $matching->sincronizar($this->busqueda->fresh());
        });

        $this->dispatch('criterios-actualizados');
    }

    public function render(): View
    {
        $rangos = array_keys(CatalogosProfesionales::rangosExperiencia());

        return view('livewire.empresa.filtros-busqueda', [
            'instituciones' => CatalogosProfesionales::instituciones(),
            'minimoExperiencia' => min($rangos),
            'maximoExperiencia' => max($rangos),
            'grupos' => [
                ['Cargo', 'cargo', CatalogosProfesionales::cargosAreas()],
                ['Carrera', 'carrera', array_keys(CatalogosProfesionales::carreras())],
                ['Especialidad', 'especialidad', $this->especialidadesDisponibles()],
                ['Industria', 'industria', CatalogosProfesionales::industrias()],
                ['Región', 'ciudad', CatalogosProfesionales::regiones()],
            ],
        ]);
    }

    /** @return list<string> */
    private function normalizarSeleccion(mixed $valor): array
    {
        return collect((array) $valor)->filter(fn (mixed $item): bool => is_string($item) && filled($item))->values()->all();
    }

    /** @return list<string> */
    private function especialidadesDisponibles(): array
    {
        return collect($this->carrera)
            ->flatMap(fn (string $carrera): array => CatalogosProfesionales::especialidades($carrera))
            ->unique()->sort()->values()->all();
    }
}
