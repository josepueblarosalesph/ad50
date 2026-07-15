<?php

namespace App\Livewire\Empresa;

use App\Concerns\FiltraPorEdad;
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
        $this->especialidad = is_array($criterios['especialidad'] ?? '') ? (string) ($criterios['especialidad'][0] ?? '') : (string) ($criterios['especialidad'] ?? '');
        $this->industria = $this->normalizarSeleccion($criterios['industria'] ?? []);
        $this->ciudad = $this->normalizarSeleccion($criterios['ciudad'] ?? []);
        $this->habilidad = $this->normalizarSeleccion($criterios['habilidad'] ?? []);
        $this->institucion = $criterios['institucion'] ?? '';
        $this->empresa = $criterios['empresa'] ?? '';
        $this->aniosMinimos = (int) ($criterios['min_anios'] ?? 0);
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
            'ciudad.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::regiones())],
            'habilidad' => ['array'],
            'habilidad.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::habilidades())],
            'institucion' => ['nullable', 'string', 'max:180'],
            'empresa' => ['nullable', 'string', 'max:180'],
            'aniosMinimos' => ['required', 'integer', Rule::in(array_keys(CatalogosProfesionales::rangosExperiencia()))],
            'palabrasClave' => ['array', 'max:10'],
            'palabrasClave.*' => ['string', 'max:100', 'distinct'],
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
                    'institucion' => $validated['institucion'],
                    'empresa' => $validated['empresa'],
                    'min_anios' => $validated['aniosMinimos'],
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
        $rangos = array_keys(CatalogosProfesionales::rangosExperiencia());

        return view('livewire.empresa.filtros-busqueda', [
            'instituciones' => CatalogosProfesionales::instituciones(),
            'empresas' => CatalogosProfesionales::empresas(),
            'limitesEdad' => CatalogosProfesionales::rangoEdad(),
            'minimoExperiencia' => min($rangos),
            'maximoExperiencia' => max($rangos),
            'grupos' => [
                ['Cargo', 'cargo', CatalogosProfesionales::cargos()],
                ['Carrera', 'carrera', CatalogosProfesionales::carrerasEstudio()],
                ['Industria', 'industria', CatalogosProfesionales::industrias()],
                ['Región', 'ciudad', CatalogosProfesionales::regiones()],
                ['Habilidades', 'habilidad', CatalogosProfesionales::habilidades()],
            ],
        ]);
    }

    /** @return list<string> */
    private function normalizarSeleccion(mixed $valor): array
    {
        return collect((array) $valor)->filter(fn (mixed $item): bool => is_string($item) && filled($item))->values()->all();
    }
}
