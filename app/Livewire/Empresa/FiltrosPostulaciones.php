<?php

namespace App\Livewire\Empresa;

use App\Concerns\FiltraPorEdad;
use App\Concerns\FiltraPorExperiencia;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Panel de filtros de las postulaciones de una publicación. Reusa los mismos criterios
 * del panel de búsquedas, pero sin persistir nada: solo emite el mapa de criterios para
 * que la lista de postulaciones se acote al vuelo.
 */
class FiltrosPostulaciones extends Component
{
    use FiltraPorEdad;
    use FiltraPorExperiencia;

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

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        $this->hidratarEdad([]);
        $this->hidratarExperiencia([]);
    }

    public function updated(string $propiedad): void
    {
        if ($propiedad === 'nuevaPalabraClave') {
            return;
        }

        $this->anunciar();
    }

    /**
     * Los selectores múltiples avisan por evento su valor (wire:model no dispara updated()).
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

        $this->anunciar();
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
        $this->anunciar();
    }

    public function quitarPalabraClave(int $index): void
    {
        unset($this->palabrasClave[$index]);
        $this->palabrasClave = array_values($this->palabrasClave);
        $this->anunciar();
    }

    public function limpiar(): void
    {
        $this->reset([
            'cargo', 'carrera', 'especialidad', 'industria', 'ciudad', 'habilidad',
            'situacionLaboral', 'genero', 'nivelEstudios', 'situacionEstudios', 'idioma',
            'actividadEconomica', 'rentaMax', 'institucion', 'empresa', 'palabrasClave', 'nuevaPalabraClave',
        ]);
        $this->hidratarEdad([]);
        $this->hidratarExperiencia([]);
        $this->anunciar();
    }

    private function anunciar(): void
    {
        $this->validate($this->reglas());

        $this->dispatch('criterios-postulaciones', criterios: $this->armarCriterios());
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

    /** @return array<string, mixed> */
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
        return view('livewire.empresa.filtros-postulaciones', [
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
}
