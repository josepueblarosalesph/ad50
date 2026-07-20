<?php

namespace App\Livewire\Empresa;

use App\Concerns\FiltraPorEdad;
use App\Concerns\FiltraPorExperiencia;
use App\Models\Busqueda;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
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

    /**
     * Criterios tal como están guardados en la búsqueda. Sirve para detectar
     * cambios pendientes y para descartarlos volviendo a este estado.
     *
     * @var array<string, mixed>
     */
    public array $criteriosGuardados = [];

    public function mount(Busqueda $busqueda): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->busqueda = $busqueda;
        $this->hidratarDesde($busqueda->criterios ?? []);
        $this->criteriosGuardados = $this->armarCriterios();
    }

    /**
     * Deja el formulario reflejando el mapa de criterios recibido.
     *
     * @param  array<string, mixed>  $criterios
     */
    private function hidratarDesde(array $criterios): void
    {
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
     * El panel se monta dos veces (barra lateral en escritorio, desplegable en móvil).
     * Cuando una instancia previsualiza, la otra adopta el mismo borrador para no
     * quedar mostrando valores viejos ni un "sin guardar" que no corresponde.
     *
     * @param  array<string, mixed>  $criterios
     */
    #[On('criterios-previsualizados')]
    public function sincronizarBorrador(array $criterios): void
    {
        if ($criterios === $this->armarCriterios()) {
            return;
        }

        $this->resetErrorBag();
        $this->hidratarDesde($criterios);
    }

    /**
     * Tras guardar (o descartar) en cualquiera de las dos instancias, ambas vuelven
     * a los criterios persistidos y renuevan su instantánea de "guardado".
     */
    #[On('criterios-guardados')]
    public function sincronizarGuardado(): void
    {
        $this->resetErrorBag();
        $this->busqueda->refresh();
        $this->hidratarDesde($this->busqueda->criterios ?? []);
        $this->criteriosGuardados = $this->armarCriterios();
    }

    /**
     * Cada criterio se previsualiza apenas cambia; el texto en edición todavía no es un criterio.
     */
    public function updated(string $propiedad): void
    {
        if ($propiedad === 'nuevaPalabraClave') {
            return;
        }

        $this->previsualizar();
    }

    /**
     * Los selectores múltiples (cargo, carrera, etc.) actualizan la propiedad vía wire:model,
     * lo que no dispara updated(); avisan por evento con su valor para reaplicar el filtro.
     *
     * @param  list<string>  $valores
     */
    #[On('criterio-actualizado')]
    public function aplicarDesdeSelector(string $campo, array $valores): void
    {
        $propiedad = Str::camel($campo);

        if (property_exists($this, $propiedad)) {
            $this->{$propiedad} = array_values(array_filter($valores, fn (mixed $valor): bool => is_string($valor) && $valor !== ''));
        }

        $this->previsualizar();
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
        $this->previsualizar();
    }

    public function quitarPalabraClave(int $index): void
    {
        unset($this->palabrasClave[$index]);
        $this->palabrasClave = array_values($this->palabrasClave);
        $this->previsualizar();
    }

    /**
     * Muestra el resultado de los criterios actuales sin tocar la base de datos:
     * el listado los evalúa al vuelo. Solo guardar() los persiste.
     */
    public function previsualizar(): void
    {
        $this->validate($this->reglas());

        $this->anunciarCriterios();
    }

    /**
     * Anuncia el mapa de criterios vigente. Lo escuchan el panel gemelo (para adoptar el
     * mismo borrador) y los selectores (para recalcular su conteo por opción), así que se
     * emite en TODO cambio: previsualizar, guardar y descartar.
     */
    private function anunciarCriterios(): void
    {
        $this->dispatch('criterios-previsualizados', criterios: $this->armarCriterios());
    }

    /**
     * Vuelve a los criterios guardados y saca al listado del modo previsualización.
     */
    public function descartar(): void
    {
        $this->sincronizarGuardado();

        $this->dispatch('criterios-guardados');
        $this->anunciarCriterios();
    }

    public function guardar(MatchingService $matching): void
    {
        $this->validate($this->reglas());
        $criterios = $this->armarCriterios();

        DB::transaction(function () use ($criterios, $matching): void {
            $this->busqueda->update([
                'rubro_oculto' => $criterios['industria'][0] ?? null,
                'criterios' => $criterios,
            ]);

            $matching->sincronizar($this->busqueda->fresh());
        });

        $this->criteriosGuardados = $criterios;

        $this->dispatch('criterios-guardados');
        $this->anunciarCriterios();
    }

    /** @return array<string, list<mixed>> */
    private function reglas(): array
    {
        return [
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
        ];
    }

    /**
     * Arma el mapa de criterios desde el estado del formulario. Se usa tanto para
     * previsualizar como para guardar, así ambos evalúan exactamente lo mismo.
     *
     * @return array<string, mixed>
     */
    private function armarCriterios(): array
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
            'renta_max' => $this->rentaMax,
            'institucion' => $this->institucion,
            'empresa' => $this->empresa,
            'experiencia' => $this->criterioExperiencia($this->expMin, $this->expMax),
            'palabra_clave' => $this->palabrasClave,
            'edad' => $this->criterioEdad($this->edadMin, $this->edadMax),
        ];
    }

    public function render(): View
    {
        return view('livewire.empresa.filtros-busqueda', [
            'sinGuardar' => $this->armarCriterios() !== $this->criteriosGuardados,
            // Se re-pasan a cada selector para que su conteo por opción sea contextual.
            'criteriosActuales' => $this->armarCriterios(),
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
