<?php

namespace App\Livewire\Empresa;

use App\Models\Publicacion;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class NuevaPublicacion extends Component
{
    public string $cargo = '';

    public string $tipoCargo = '';

    public int $vacantes = 1;

    public string $descripcion = '';

    public string $modalidad = '';

    public string $pais = 'Chile';

    public string $comuna = '';

    public string $actividadEmpresa = '';

    public string $jerarquia = '';

    public ?int $sueldo = null;

    public bool $mostrarSueldo = false;

    public string $requisitos = '';

    public string $experienciaLaboral = '';

    public string $estudiosMinimos = '';

    public string $situacionAcademica = '';

    public string $competenciasTexto = '';

    /** @var list<string> */
    public array $idiomas = [];

    /** @var list<string> */
    public array $preguntas = [];

    public bool $empleoInclusivo = false;

    public bool $postulacionFacil = true;

    public bool $notificarPostulaciones = true;

    public bool $evaluacionOnline = false;

    public bool $evaluacionManual = false;

    public int $vigenciaDias = 30;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
    }

    public function agregarPregunta(): void
    {
        if (count($this->preguntas) < 10) {
            $this->preguntas[] = '';
        }
    }

    public function quitarPregunta(int $index): void
    {
        unset($this->preguntas[$index]);
        $this->preguntas = array_values($this->preguntas);
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'cargo' => ['required', 'string', 'max:100'],
            'tipoCargo' => ['required', Rule::in(CatalogosProfesionales::tiposTrabajo())],
            'vacantes' => ['required', 'integer', 'min:1', 'max:100'],
            'descripcion' => ['required', 'string', 'min:150', 'max:8000'],
            'modalidad' => ['required', Rule::in(['Presencial', 'Híbrida', 'Remota'])],
            'pais' => ['required', 'string', 'max:80'],
            'comuna' => ['required', 'string', 'max:120'],
            'actividadEmpresa' => ['required', Rule::in(CatalogosProfesionales::industrias())],
            'jerarquia' => ['required', Rule::in(CatalogosProfesionales::jerarquias())],
            'sueldo' => ['nullable', 'integer', 'min:100000', 'max:100000000'],
            'mostrarSueldo' => ['boolean'],
            'requisitos' => ['required', 'string', 'max:1000'],
            'experienciaLaboral' => ['required', Rule::in(array_values(CatalogosProfesionales::rangosExperiencia()))],
            'estudiosMinimos' => ['required', Rule::in(CatalogosProfesionales::nivelesEstudio())],
            'situacionAcademica' => ['required', Rule::in(CatalogosProfesionales::situacionesEstudio())],
            'competenciasTexto' => ['nullable', 'string', 'max:1000'],
            'idiomas' => ['array'],
            'idiomas.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::idiomas())],
            'preguntas' => ['array', 'max:10'],
            'preguntas.*' => ['nullable', 'string', 'max:300'],
            'empleoInclusivo' => ['boolean'],
            'postulacionFacil' => ['boolean'],
            'notificarPostulaciones' => ['boolean'],
            'evaluacionOnline' => ['boolean'],
            'evaluacionManual' => ['boolean'],
            'vigenciaDias' => ['required', Rule::in([15, 30, 60, 90])],
        ]);

        $competencias = str($validated['competenciasTexto'])
            ->explode(',')
            ->map(fn (string $competencia): string => trim($competencia))
            ->filter()
            ->unique()
            ->values()
            ->all();

        Publicacion::query()->create([
            'empresa_id' => auth()->user()->empresa->id,
            'cargo' => $validated['cargo'],
            'tipo_cargo' => $validated['tipoCargo'],
            'vacantes' => $validated['vacantes'],
            'nombre_empresa' => auth()->user()->empresa->razon_social,
            'descripcion' => $validated['descripcion'],
            'modalidad' => $validated['modalidad'],
            'pais' => $validated['pais'],
            'comuna' => $validated['comuna'],
            'actividad_empresa' => $validated['actividadEmpresa'],
            'jerarquia' => $validated['jerarquia'],
            'sueldo' => $validated['sueldo'],
            'mostrar_sueldo' => $validated['mostrarSueldo'],
            'requisitos' => $validated['requisitos'],
            'experiencia_laboral' => $validated['experienciaLaboral'],
            'estudios_minimos' => $validated['estudiosMinimos'],
            'situacion_academica' => $validated['situacionAcademica'],
            'competencias' => $competencias,
            'idiomas' => $validated['idiomas'],
            'preguntas' => collect($validated['preguntas'])->filter()->values()->all(),
            'empleo_inclusivo' => $validated['empleoInclusivo'],
            'postulacion_facil' => $validated['postulacionFacil'],
            'notificar_postulaciones' => $validated['notificarPostulaciones'],
            'evaluacion_online' => $validated['evaluacionOnline'],
            'evaluacion_manual' => $validated['evaluacionManual'],
            'vigencia_dias' => $validated['vigenciaDias'],
            'vigente_hasta' => today()->addDays($validated['vigenciaDias']),
            'estado' => 'publicada',
        ]);

        session()->flash('status', 'La publicación quedó visible para los postulantes.');
        $this->redirectRoute('empresa.publicaciones.index', navigate: true);
    }

    #[Title('Nueva publicación · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.nueva-publicacion', [
            'tiposCargo' => CatalogosProfesionales::tiposTrabajo(),
            'actividades' => CatalogosProfesionales::industrias(),
            'jerarquias' => CatalogosProfesionales::jerarquias(),
            'experiencias' => array_values(CatalogosProfesionales::rangosExperiencia()),
            'estudios' => CatalogosProfesionales::nivelesEstudio(),
            'situacionesAcademicas' => CatalogosProfesionales::situacionesEstudio(),
            'idiomasDisponibles' => CatalogosProfesionales::idiomas(),
        ]);
    }
}
