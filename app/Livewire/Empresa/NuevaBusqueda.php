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
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class NuevaBusqueda extends Component
{
    use FiltraPorEdad;
    use FiltraPorExperiencia;

    public ?Busqueda $busqueda = null;

    public string $titulo = '';

    /** @var list<string> */
    public array $cargo = [];

    /** @var list<string> */
    public array $industria = [];

    /** @var list<string> */
    public array $carrera = [];

    public string $especialidad = '';

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

    /** @var list<string> */
    public array $palabrasClave = [];

    public string $nuevaPalabraClave = '';

    public string $institucion = '';

    public string $empresa = '';

    public function mount(?Busqueda $busqueda = null): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        if ($busqueda === null) {
            $this->hidratarEdad([]);
            $this->hidratarExperiencia([]);

            return;
        }

        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->busqueda = $busqueda;
        $criterios = $busqueda->criterios ?? [];
        $this->titulo = $busqueda->titulo;
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
            'palabrasClave' => ['array', 'max:10'],
            'palabrasClave.*' => ['string', 'max:100', 'distinct'],
            'institucion' => ['nullable', 'string', 'max:180'],
            'empresa' => ['nullable', 'string', 'max:180'],
            ...$this->reglasExperiencia(),
            ...$this->reglasEdad(),
        ]);

        $criterios = $this->armarCriterios();

        $busqueda = DB::transaction(function () use ($validated, $criterios, $matching): Busqueda {
            $atributos = [
                'titulo' => $validated['titulo'],
                'rubro_oculto' => $criterios['industria'][0] ?? null,
                'criterios' => $criterios,
            ];

            if ($this->busqueda) {
                // Al editar se preservan tanto la etapa del proceso como la fecha de creación.
                $this->busqueda->update($atributos);
                $busqueda = $this->busqueda->fresh();
            } else {
                $busqueda = Busqueda::query()->create([
                    'empresa_id' => auth()->user()->empresa->id,
                    'estado' => 'long_list',
                    ...$atributos,
                ]);
            }

            $matching->sincronizar($busqueda);

            return $busqueda;
        });

        $this->redirectRoute('empresa.resultados', ['busqueda' => $busqueda], navigate: true);
    }

    /**
     * Arma el mapa de criterios desde el estado del formulario. Lo usan tanto save()
     * como el conteo contextual de los selectores, así ambos miran lo mismo.
     *
     * @return array<string, mixed>
     */
    public function armarCriterios(): array
    {
        return [
            'cargo' => $this->cargo,
            'carrera' => $this->carrera,
            'especialidad' => $this->especialidad,
            'industria' => $this->industria,
            'ciudad' => $this->ciudad,
            'habilidad' => $this->habilidad,
            'situacion_laboral' => $this->situacionLaboral,
            'genero' => $this->genero,
            'nivel_estudios' => $this->nivelEstudios,
            'situacion_estudios' => $this->situacionEstudios,
            'idioma' => $this->idioma,
            'actividad_economica' => $this->actividadEconomica,
            'renta_max' => (int) $this->rentaMax,
            'institucion' => $this->institucion,
            'empresa' => $this->empresa,
            'experiencia' => $this->criterioExperiencia($this->expMin, $this->expMax),
            'palabra_clave' => $this->palabrasClave,
            'edad' => $this->criterioEdad($this->edadMin, $this->edadMax),
        ];
    }

    #[Title('Configurar búsqueda · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.nueva-busqueda', [
            'instituciones' => CatalogosProfesionales::instituciones(),
            'empresas' => CatalogosProfesionales::empresas(),
            'limitesExperiencia' => CatalogosProfesionales::rangoExperiencia(),
            'limitesEdad' => CatalogosProfesionales::rangoEdad(),
            'editando' => $this->busqueda !== null,
            'criteriosActuales' => $this->armarCriterios(),
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
}
