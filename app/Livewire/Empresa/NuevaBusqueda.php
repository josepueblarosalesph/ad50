<?php

namespace App\Livewire\Empresa;

use App\Concerns\FiltraPorEdad;
use App\Models\Busqueda;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class NuevaBusqueda extends Component
{
    use FiltraPorEdad;

    public ?Busqueda $busqueda = null;

    public string $titulo = '';

    /** @var list<string> */
    public array $cargo = [];

    /** @var list<string> */
    public array $industria = [];

    /** @var list<string> */
    public array $carrera = [];

    /** @var list<string> */
    public array $especialidad = [];

    /** @var list<string> */
    public array $ciudad = [];

    /** @var list<string> */
    public array $palabrasClave = [];

    public string $nuevaPalabraClave = '';

    public string $institucion = '';

    public string $empresa = '';

    public int $aniosMinimos = 0;

    public function mount(?Busqueda $busqueda = null): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        if ($busqueda === null) {
            $this->hidratarEdad([]);

            return;
        }

        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->busqueda = $busqueda;
        $criterios = $busqueda->criterios ?? [];
        $this->titulo = $busqueda->titulo;
        $this->cargo = $this->normalizarSeleccion($criterios['cargo'] ?? []);
        $this->carrera = $this->normalizarSeleccion($criterios['carrera'] ?? []);
        $this->especialidad = $this->normalizarSeleccion($criterios['especialidad'] ?? []);
        $this->industria = $this->normalizarSeleccion($criterios['industria'] ?? []);
        $this->ciudad = $this->normalizarSeleccion($criterios['ciudad'] ?? []);
        $this->institucion = $criterios['institucion'] ?? '';
        $this->empresa = $criterios['empresa'] ?? '';
        $this->aniosMinimos = (int) ($criterios['min_anios'] ?? 0);
        $this->palabrasClave = $this->normalizarSeleccion($criterios['palabra_clave'] ?? []);
        $this->hidratarEdad($criterios);
    }

    public function updatedCarrera(): void
    {
        $this->especialidad = array_values(array_intersect($this->especialidad, $this->especialidadesDisponibles()));
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
    }

    public function quitarPalabraClave(int $index): void
    {
        unset($this->palabrasClave[$index]);
        $this->palabrasClave = array_values($this->palabrasClave);
    }

    public function save(MatchingService $matching): void
    {
        $validated = $this->validate([
            'titulo' => ['required', 'string', 'max:180'],
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
            'palabrasClave' => ['array', 'max:10'],
            'palabrasClave.*' => ['string', 'max:100', 'distinct'],
            'institucion' => ['nullable', 'string', 'max:180'],
            'empresa' => ['nullable', 'string', 'max:180'],
            'aniosMinimos' => ['required', 'integer', Rule::in(array_keys(CatalogosProfesionales::rangosExperiencia()))],
            ...$this->reglasEdad(),
        ]);

        $busqueda = DB::transaction(function () use ($validated, $matching): Busqueda {
            $atributos = [
                'titulo' => $validated['titulo'],
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
                    'edad' => $this->criterioEdad($validated['edadMin'], $validated['edadMax']),
                ],
                'estado' => 'activa',
            ];

            if ($this->busqueda) {
                $this->busqueda->update($atributos);
                $busqueda = $this->busqueda->fresh();
            } else {
                $busqueda = Busqueda::query()->create([
                    'empresa_id' => auth()->user()->empresa->id,
                    ...$atributos,
                ]);
            }

            $matching->sincronizar($busqueda);

            return $busqueda;
        });

        $this->redirectRoute('empresa.resultados', ['busqueda' => $busqueda], navigate: true);
    }

    #[Title('Configurar búsqueda · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.nueva-busqueda', [
            'cargosAreas' => CatalogosProfesionales::cargosAreas(),
            'carreras' => array_keys(CatalogosProfesionales::carreras()),
            'especialidades' => $this->especialidadesDisponibles(),
            'industrias' => CatalogosProfesionales::industrias(),
            'ciudades' => CatalogosProfesionales::regiones(),
            'instituciones' => CatalogosProfesionales::instituciones(),
            'rangosExperiencia' => CatalogosProfesionales::rangosExperiencia(),
            'limitesEdad' => CatalogosProfesionales::rangoEdad(),
            'editando' => $this->busqueda !== null,
        ]);
    }

    /** @return list<string> */
    private function normalizarSeleccion(mixed $valor): array
    {
        return collect((array) $valor)
            ->filter(fn (mixed $item): bool => is_string($item) && filled($item))
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function especialidadesDisponibles(): array
    {
        return collect($this->carrera)
            ->flatMap(fn (string $carrera): array => CatalogosProfesionales::especialidades($carrera))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
