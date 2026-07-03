<?php

namespace App\Livewire\Postulante;

use App\Models\Postulante;
use App\Rules\RutValido;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use App\Support\Rut;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

class Ficha extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $rut = '';

    public ?int $anioNacimiento = null;

    public string $telefono = '';

    public string $linkedin = '';

    public string $ciudad = '';

    public string $cargoActual = '';

    public string $industria = '';

    public string $industria2 = '';

    public string $industria3 = '';

    public string $carrera = '';

    public string $universidad = '';

    public string $especialidad = '';

    public string $postgrado = '';

    public string $empresaActual = '';

    public string $experienciaArea = '';

    public ?int $experienciaInicio = null;

    public ?int $experienciaFin = null;

    public string $resumenProfesional = '';

    public int $aniosExperiencia = 0;

    /** @var array<int, array<string, mixed>> */
    public array $educaciones = [];

    /** @var array<int, array{idioma: string, nivel: string}> */
    public array $idiomas = [];

    /** @var array<int, array<string, mixed>> */
    public array $experiencias = [];

    public int $completitud = 0;

    public bool $visible = true;

    public mixed $cv = null;

    public ?string $cvRutaExistente = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'postulante', 403);

        $postulante = auth()->user()->postulante;

        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
        $this->rut = Rut::formatear($postulante?->rut ?? '');
        $this->anioNacimiento = $postulante?->anio_nacimiento;
        $this->telefono = $postulante?->telefono ?? '';
        $this->linkedin = $postulante?->linkedin ?? '';
        $this->ciudad = $postulante?->ciudad ?? '';
        $this->cargoActual = $postulante?->cargo_actual ?? '';
        $this->industria = $postulante?->industria ?? '';
        $this->industria2 = $postulante?->industria_2 ?? '';
        $this->industria3 = $postulante?->industria_3 ?? '';
        $this->carrera = $postulante?->carrera ?? '';
        $this->universidad = $postulante?->universidad ?? '';
        $this->especialidad = $postulante?->especialidad ?? '';
        $this->postgrado = $postulante?->postgrado ?? '';
        $educacionesGuardadas = $postulante?->educaciones ?: [[
            'nivel' => filled($postulante?->postgrado) ? 'Postgrado' : 'Universitaria',
            'pais' => 'Chile',
            'institucion' => $postulante?->universidad ?? '',
            'carrera' => $postulante?->carrera ?? '',
            'mencion' => $postulante?->especialidad ?? '',
            'modalidad' => '',
            'situacion' => filled($postulante?->carrera) ? 'Titulado' : '',
        ]];
        $this->educaciones = collect($educacionesGuardadas)
            ->map(fn (array $educacion): array => $this->normalizarEducacion($educacion))
            ->values()
            ->all();
        $this->idiomas = $postulante?->idiomas ?: [$this->nuevoIdioma()];
        $this->empresaActual = $postulante?->empresa_actual ?? '';
        $this->experienciaArea = $postulante?->experiencia_area ?? '';
        $this->experienciaInicio = $postulante?->experiencia_inicio;
        $this->experienciaFin = $postulante?->experiencia_fin;
        $this->resumenProfesional = $postulante?->resumen_profesional ?? '';
        $this->aniosExperiencia = $postulante?->anios_experiencia ?? 0;
        $experienciasGuardadas = $postulante?->experiencias ?: [[
            'cargo' => $postulante?->cargo_actual ?? '',
            'empresa' => $postulante?->empresa_actual ?? '',
            'area' => $postulante?->experiencia_area ?? '',
            'inicio' => $postulante?->experiencia_inicio,
            'fin' => $postulante?->experiencia_fin,
        ]];
        $this->experiencias = collect($experienciasGuardadas)
            ->map(fn (array $experiencia): array => $this->normalizarExperiencia($experiencia))
            ->values()
            ->all();
        $this->completitud = $postulante?->completitud ?? 0;
        $this->visible = $postulante?->visible ?? true;
        $this->cvRutaExistente = $postulante?->cv_ruta;
    }

    public function updatedRut(): void
    {
        $this->rut = Rut::formatear($this->rut);
        $this->validateOnly('rut', ['rut' => ['required', 'string', 'max:20', new RutValido]]);
    }

    public function addExperiencia(): void
    {
        if (count($this->experiencias) >= 20) {
            return;
        }

        $this->experiencias[] = $this->nuevaExperiencia();
    }

    public function addEducacion(): void
    {
        if (count($this->educaciones) >= 20) {
            return;
        }

        $this->educaciones[] = $this->nuevaEducacion();
    }

    public function removeEducacion(int $index): void
    {
        if (count($this->educaciones) === 1 || ! isset($this->educaciones[$index])) {
            return;
        }

        unset($this->educaciones[$index]);
        $this->educaciones = array_values($this->educaciones);
    }

    public function addIdioma(): void
    {
        if (count($this->idiomas) >= count(CatalogosProfesionales::idiomas())) {
            return;
        }

        $this->idiomas[] = $this->nuevoIdioma();
    }

    public function removeIdioma(int $index): void
    {
        if (count($this->idiomas) === 1 || ! isset($this->idiomas[$index])) {
            return;
        }

        unset($this->idiomas[$index]);
        $this->idiomas = array_values($this->idiomas);
    }

    public function removeExperiencia(int $index): void
    {
        if (count($this->experiencias) === 1 || ! isset($this->experiencias[$index])) {
            return;
        }

        unset($this->experiencias[$index]);
        $this->experiencias = array_values($this->experiencias);
    }

    public function save(MatchingService $matching): void
    {
        $this->rut = Rut::formatear($this->rut);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
            'rut' => ['required', 'string', 'max:20', new RutValido],
            'anioNacimiento' => ['required', 'integer', 'min:1900', 'max:'.now()->year],
            'telefono' => ['nullable', 'string', 'max:30'],
            'linkedin' => ['nullable', 'url:http,https', 'max:255'],
            'ciudad' => ['required', Rule::in(CatalogosProfesionales::ciudades())],
            'industria' => ['required', Rule::in(CatalogosProfesionales::industrias())],
            'industria2' => ['nullable', Rule::in(CatalogosProfesionales::industrias()), 'different:industria'],
            'industria3' => ['nullable', Rule::in(CatalogosProfesionales::industrias()), 'different:industria', 'different:industria2'],
            'educaciones' => ['required', 'array', 'min:1', 'max:20'],
            'educaciones.*.nivel' => ['required', Rule::in(CatalogosProfesionales::nivelesEstudio())],
            'educaciones.*.pais' => ['required', 'string', 'max:100'],
            'educaciones.*.institucion' => ['required', 'string', 'max:180'],
            'educaciones.*.carrera' => ['nullable', 'string', 'max:180'],
            'educaciones.*.mencion' => ['nullable', 'string', 'max:180'],
            'educaciones.*.modalidad' => ['nullable', Rule::in(CatalogosProfesionales::modalidadesEstudio())],
            'educaciones.*.situacion' => ['nullable', Rule::in(CatalogosProfesionales::situacionesEstudio())],
            'educaciones.*.inicio_anio' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'educaciones.*.termino_anio' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'educaciones.*.egreso_anio' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'idiomas' => ['required', 'array', 'min:1', 'max:'.count(CatalogosProfesionales::idiomas())],
            'idiomas.*.idioma' => ['required', Rule::in(CatalogosProfesionales::idiomas()), 'distinct:strict'],
            'idiomas.*.nivel' => ['required', Rule::in(CatalogosProfesionales::nivelesIdioma())],
            'experiencias' => ['required', 'array', 'min:1', 'max:20'],
            'experiencias.*.cargo' => ['required', 'string', 'max:160'],
            'experiencias.*.tipo_trabajo' => ['required', Rule::in(CatalogosProfesionales::tiposTrabajo())],
            'experiencias.*.empresa' => ['required', 'string', 'max:160'],
            'experiencias.*.jerarquia' => ['required', Rule::in(CatalogosProfesionales::jerarquias())],
            'experiencias.*.actividad_empresa' => ['required', Rule::in(CatalogosProfesionales::industrias())],
            'experiencias.*.inicio_mes' => ['required', 'integer', 'between:1,12'],
            'experiencias.*.inicio_anio' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
            'experiencias.*.actualmente' => ['boolean'],
            'experiencias.*.fin_mes' => ['nullable', 'integer', 'between:1,12'],
            'experiencias.*.fin_anio' => ['nullable', 'integer', 'min:1950', 'max:'.now()->year],
            'experiencias.*.responsabilidades' => ['required', 'string', 'max:3000'],
            'resumenProfesional' => ['nullable', 'string', 'max:2000'],
            'visible' => ['boolean'],
            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        foreach ($validated['educaciones'] as $index => $educacion) {
            $esEscolar = in_array($educacion['nivel'], CatalogosProfesionales::nivelesEscolares(), true);

            if ($esEscolar) {
                if ($educacion['egreso_anio'] === null) {
                    $this->addError("educaciones.$index.egreso_anio", 'El año de egreso es obligatorio.');
                }

                $validated['educaciones'][$index] = array_replace($educacion, [
                    'carrera' => null,
                    'mencion' => null,
                    'modalidad' => null,
                    'situacion' => null,
                    'inicio_anio' => null,
                    'termino_anio' => null,
                ]);

                continue;
            }

            foreach (['carrera', 'mencion', 'modalidad', 'situacion', 'inicio_anio', 'termino_anio'] as $campo) {
                if (blank($educacion[$campo])) {
                    $this->addError("educaciones.$index.$campo", 'Este campo es obligatorio para el nivel seleccionado.');
                }
            }

            if ($educacion['inicio_anio'] !== null && $educacion['termino_anio'] !== null && $educacion['termino_anio'] < $educacion['inicio_anio']) {
                $this->addError("educaciones.$index.termino_anio", 'El año de término debe ser posterior al de inicio.');
            }

            $validated['educaciones'][$index]['egreso_anio'] = null;
        }

        foreach ($validated['experiencias'] as $index => $experiencia) {
            if ($experiencia['actualmente']) {
                $validated['experiencias'][$index]['fin_mes'] = null;
                $validated['experiencias'][$index]['fin_anio'] = null;

                continue;
            }

            if ($experiencia['fin_mes'] === null || $experiencia['fin_anio'] === null) {
                $this->addError("experiencias.$index.fin_anio", 'Indica la fecha de término o marca que actualmente trabajas aquí.');

                continue;
            }

            $inicio = ($experiencia['inicio_anio'] * 12) + $experiencia['inicio_mes'];
            $fin = ($experiencia['fin_anio'] * 12) + $experiencia['fin_mes'];

            if ($fin < $inicio) {
                $this->addError("experiencias.$index.fin_anio", 'La fecha de término debe ser posterior a la de inicio.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $validated['aniosExperiencia'] = $this->calculateAniosExperiencia($validated['experiencias']);
        $principal = $validated['experiencias'][0];
        $educacionPrincipal = collect($validated['educaciones'])
            ->first(fn (array $educacion): bool => ! in_array($educacion['nivel'], CatalogosProfesionales::nivelesEscolares(), true))
            ?? $validated['educaciones'][0];

        $completitud = $this->calculateCompletitud($validated);

        $cvRutaAnterior = auth()->user()->postulante?->cv_ruta;
        $cvRutaNueva = isset($validated['cv']) && $validated['cv'] !== null
            ? $validated['cv']->store('cvs', 'local')
            : null;

        if ($cvRutaNueva === false) {
            $this->addError('cv', 'No pudimos guardar el archivo. Inténtalo nuevamente.');

            return;
        }

        try {
            $postulante = DB::transaction(function () use ($validated, $completitud, $principal, $educacionPrincipal, $cvRutaAnterior, $cvRutaNueva): Postulante {
                auth()->user()->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                ]);

                return Postulante::query()->updateOrCreate(['user_id' => auth()->id()], [
                    'rut' => $validated['rut'],
                    'anio_nacimiento' => $validated['anioNacimiento'],
                    'telefono' => $validated['telefono'],
                    'linkedin' => $validated['linkedin'],
                    'ciudad' => $validated['ciudad'],
                    'cargo_actual' => $principal['cargo'],
                    'industria' => $validated['industria'],
                    'industria_2' => $validated['industria2'],
                    'industria_3' => $validated['industria3'],
                    'carrera' => $educacionPrincipal['carrera'],
                    'universidad' => $educacionPrincipal['institucion'],
                    'especialidad' => $educacionPrincipal['mencion'],
                    'postgrado' => in_array($educacionPrincipal['nivel'], ['Postgrado', 'Magíster', 'Doctorado'], true) ? $educacionPrincipal['carrera'] : null,
                    'educaciones' => $validated['educaciones'],
                    'idiomas' => $validated['idiomas'],
                    'empresa_actual' => $principal['empresa'],
                    'experiencia_area' => $principal['actividad_empresa'],
                    'experiencia_inicio' => $principal['inicio_anio'],
                    'experiencia_fin' => $principal['fin_anio'],
                    'experiencias' => $validated['experiencias'],
                    'resumen_profesional' => $validated['resumenProfesional'],
                    'anios_experiencia' => $validated['aniosExperiencia'],
                    'visible' => $validated['visible'],
                    'completitud' => $completitud,
                    'cv_ruta' => $cvRutaNueva ?? $cvRutaAnterior,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($cvRutaNueva !== null) {
                Storage::disk('local')->delete($cvRutaNueva);
            }

            throw $exception;
        }

        if ($cvRutaNueva !== null && $cvRutaAnterior !== null) {
            Storage::disk('local')->delete($cvRutaAnterior);
        }

        $matching->sincronizarPostulante($postulante);

        $this->completitud = $completitud;
        $this->aniosExperiencia = $validated['aniosExperiencia'];
        $this->cvRutaExistente = $postulante->cv_ruta;
        $this->reset('cv');

        session()->flash('status', 'Ficha actualizada correctamente.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function calculateCompletitud(array $validated): int
    {
        $requiredFields = [
            'name',
            'email',
            'rut',
            'anioNacimiento',
            'industria',
            'educaciones',
            'idiomas',
            'experiencias',
        ];

        $completedFields = collect($requiredFields)
            ->filter(fn (string $field): bool => filled($validated[$field] ?? null))
            ->count();

        return (int) round(($completedFields / count($requiredFields)) * 100);
    }

    /**
     * @param  array<int, array<string, mixed>>  $experiencias
     */
    private function calculateAniosExperiencia(array $experiencias): int
    {
        $intervalos = collect($experiencias)
            ->map(fn (array $experiencia): array => [
                ((int) $experiencia['inicio_anio'] * 12) + (int) $experiencia['inicio_mes'],
                ((int) ($experiencia['fin_anio'] ?? now()->year) * 12) + (int) ($experiencia['fin_mes'] ?? now()->month),
            ])
            ->sortBy(0)
            ->values();

        $fusionados = [];

        foreach ($intervalos as [$inicio, $fin]) {
            $ultimo = array_key_last($fusionados);

            if ($ultimo === null || $inicio > $fusionados[$ultimo][1]) {
                $fusionados[] = [$inicio, $fin];
            } else {
                $fusionados[$ultimo][1] = max($fusionados[$ultimo][1], $fin);
            }
        }

        $meses = collect($fusionados)->sum(fn (array $intervalo): int => $intervalo[1] - $intervalo[0]);

        return min(80, (int) floor($meses / 12));
    }

    /** @return array<string, mixed> */
    private function nuevaExperiencia(): array
    {
        return [
            'cargo' => '',
            'tipo_trabajo' => 'Jornada completa',
            'empresa' => '',
            'jerarquia' => '',
            'actividad_empresa' => '',
            'inicio_mes' => null,
            'inicio_anio' => null,
            'actualmente' => false,
            'fin_mes' => null,
            'fin_anio' => null,
            'responsabilidades' => '',
        ];
    }

    /** @return array<string, mixed> */
    private function nuevaEducacion(): array
    {
        return [
            'nivel' => '',
            'pais' => 'Chile',
            'institucion' => '',
            'carrera' => null,
            'mencion' => null,
            'modalidad' => null,
            'situacion' => null,
            'inicio_anio' => null,
            'termino_anio' => null,
            'egreso_anio' => null,
        ];
    }

    /** @return array{idioma: string, nivel: string} */
    private function nuevoIdioma(): array
    {
        return ['idioma' => '', 'nivel' => ''];
    }

    /**
     * @param  array<string, mixed>  $educacion
     * @return array<string, mixed>
     */
    private function normalizarEducacion(array $educacion): array
    {
        return array_replace($this->nuevaEducacion(), $educacion);
    }

    /**
     * @param  array<string, mixed>  $experiencia
     * @return array<string, mixed>
     */
    private function normalizarExperiencia(array $experiencia): array
    {
        $finAnterior = $experiencia['fin'] ?? null;

        return array_replace($this->nuevaExperiencia(), [
            'cargo' => $experiencia['cargo'] ?? '',
            'tipo_trabajo' => $experiencia['tipo_trabajo'] ?? 'Jornada completa',
            'empresa' => $experiencia['empresa'] ?? '',
            'jerarquia' => $experiencia['jerarquia'] ?? 'Profesional / Especialista',
            'actividad_empresa' => $experiencia['actividad_empresa'] ?? $experiencia['area'] ?? '',
            'inicio_mes' => $experiencia['inicio_mes'] ?? 1,
            'inicio_anio' => $experiencia['inicio_anio'] ?? $experiencia['inicio'] ?? null,
            'actualmente' => $experiencia['actualmente'] ?? empty($finAnterior),
            'fin_mes' => $experiencia['fin_mes'] ?? ($finAnterior ? 12 : null),
            'fin_anio' => $experiencia['fin_anio'] ?? $finAnterior,
            'responsabilidades' => $experiencia['responsabilidades'] ?? $this->resumenProfesional,
        ]);
    }

    #[Title('Mi ficha profesional · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.postulante.ficha', [
            'industrias' => CatalogosProfesionales::industrias(),
            'ciudades' => CatalogosProfesionales::ciudades(),
            'tiposTrabajo' => CatalogosProfesionales::tiposTrabajo(),
            'jerarquias' => CatalogosProfesionales::jerarquias(),
            'meses' => CatalogosProfesionales::meses(),
            'nivelesEstudio' => CatalogosProfesionales::nivelesEstudio(),
            'nivelesEscolares' => CatalogosProfesionales::nivelesEscolares(),
            'modalidadesEstudio' => CatalogosProfesionales::modalidadesEstudio(),
            'situacionesEstudio' => CatalogosProfesionales::situacionesEstudio(),
            'idiomasDisponibles' => CatalogosProfesionales::idiomas(),
            'nivelesIdioma' => CatalogosProfesionales::nivelesIdioma(),
        ]);
    }
}
