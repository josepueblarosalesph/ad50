<?php

namespace App\Livewire\Empresa;

use App\Models\Publicacion;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class NuevaPublicacion extends Component
{
    public ?Publicacion $publicacion = null;

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

    public function mount(?Publicacion $publicacion = null): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        if ($publicacion === null || ! $publicacion->exists) {
            // El cupo del plan solo limita crear publicaciones nuevas; editar una
            // existente sigue disponible aunque el cupo esté agotado.
            $this->redirigirSinCupo();

            return;
        }

        abort_unless($publicacion->empresa_id === auth()->user()->empresa?->id, 403);

        $this->publicacion = $publicacion;
        $this->cargo = $publicacion->cargo;
        $this->tipoCargo = $publicacion->tipo_cargo;
        $this->vacantes = $publicacion->vacantes;
        $this->descripcion = $publicacion->descripcion;
        $this->modalidad = $publicacion->modalidad;
        $this->pais = $publicacion->pais;
        $this->comuna = $publicacion->comuna;
        $this->actividadEmpresa = $publicacion->actividad_empresa;
        $this->jerarquia = $publicacion->jerarquia;
        $this->sueldo = $publicacion->sueldo;
        $this->mostrarSueldo = $publicacion->mostrar_sueldo;
        $this->requisitos = $publicacion->requisitos;
        $this->experienciaLaboral = $publicacion->experiencia_laboral;
        $this->estudiosMinimos = $publicacion->estudios_minimos;
        $this->situacionAcademica = $publicacion->situacion_academica;
        $this->competenciasTexto = implode(', ', $publicacion->competencias ?? []);
        $this->idiomas = $publicacion->idiomas ?? [];
        $this->preguntas = $publicacion->preguntas ?? [];
        $this->empleoInclusivo = $publicacion->empleo_inclusivo;
        $this->postulacionFacil = $publicacion->postulacion_facil;
        $this->notificarPostulaciones = $publicacion->notificar_postulaciones;
        $this->evaluacionOnline = $publicacion->evaluacion_online;
        $this->evaluacionManual = $publicacion->evaluacion_manual;
        $this->vigenciaDias = $publicacion->vigencia_dias;
    }

    /**
     * Manda de vuelta al listado si la empresa agotó el cupo de publicaciones del plan.
     * Devuelve true cuando redirigió, para que quien lo llame corte su ejecución.
     */
    private function redirigirSinCupo(): bool
    {
        if (auth()->user()->empresa?->puedePublicar()) {
            return false;
        }

        session()->flash('publicacion_error', 'Alcanzaste el máximo de publicaciones de tu plan. Cambia de plan para publicar más.');
        $this->redirectRoute('empresa.publicaciones.index', navigate: true);

        return true;
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
        // Revalida el cupo al guardar: entre que se abrió el formulario y este envío,
        // otro usuario del equipo pudo consumir la última publicación disponible.
        if ($this->publicacion === null && $this->redirigirSinCupo()) {
            return;
        }

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

        $atributos = [
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
        ];

        if ($this->publicacion) {
            // Cambiar la vigencia reinicia el conteo desde hoy; si no se toca, la fecha
            // de término original se respeta para no alargar la oferta sin querer.
            if ($this->publicacion->vigencia_dias !== $validated['vigenciaDias']) {
                $atributos['vigente_hasta'] = today()->addDays($validated['vigenciaDias']);
            }

            $this->publicacion->update($atributos);

            session()->flash('status', 'Actualizamos la publicación «'.$this->publicacion->cargo.'».');
            $this->redirectRoute('empresa.publicaciones.show', ['publicacion' => $this->publicacion], navigate: true);

            return;
        }

        Publicacion::query()->create([
            'empresa_id' => auth()->user()->empresa->id,
            'vigente_hasta' => today()->addDays($validated['vigenciaDias']),
            'estado' => 'publicada',
            ...$atributos,
        ]);

        session()->flash('status', 'La publicación quedó visible para los postulantes.');
        $this->redirectRoute('empresa.publicaciones.index', navigate: true);
    }

    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.nueva-publicacion', [
            'editando' => $this->publicacion !== null,
            'publicacionesDisponibles' => auth()->user()->empresa?->publicacionesDisponibles(),
            'tiposCargo' => CatalogosProfesionales::tiposTrabajo(),
            'actividades' => CatalogosProfesionales::industrias(),
            'jerarquias' => CatalogosProfesionales::jerarquias(),
            'experiencias' => array_values(CatalogosProfesionales::rangosExperiencia()),
            'estudios' => CatalogosProfesionales::nivelesEstudio(),
            'situacionesAcademicas' => CatalogosProfesionales::situacionesEstudio(),
            'idiomasDisponibles' => CatalogosProfesionales::idiomas(),
        ])->title($this->publicacion ? 'Editar publicación · AD+50' : 'Nueva publicación · AD+50');
    }
}
