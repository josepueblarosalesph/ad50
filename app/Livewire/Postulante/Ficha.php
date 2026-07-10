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

    public string $nombres = '';

    public string $apellidos = '';

    public string $email = '';

    public string $tipoDocumento = 'rut';

    public string $rut = '';

    public ?int $anioNacimiento = null;

    public string $genero = '';

    public string $nacionalidad = 'Chilena';

    public string $titular = '';

    public string $telefono = '';

    public string $linkedin = '';

    public string $sitioWeb = '';

    public string $ciudad = '';

    /** @var array<int, string> */
    public array $regionesInteres = [];

    /** @var array<int, string> */
    public array $modalidadesTrabajo = [];

    public string $situacionLaboral = '';

    public ?int $expectativaRenta = null;

    public string $cargoActual = '';

    /** @var array<int, string> */
    public array $industriasInteres = [];

    public string $carrera = '';

    public string $universidad = '';

    public string $especialidad = '';

    public string $postgrado = '';

    public string $empresaActual = '';

    public string $experienciaArea = '';

    public ?int $experienciaInicio = null;

    public ?int $experienciaFin = null;

    public string $resumenProfesional = '';

    public ?int $aniosExperiencia = null;

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

    public bool $modoOnboarding = false;

    public int $pasoActual = 1;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'postulante', 403);

        $postulante = auth()->user()->postulante;

        $this->modoOnboarding = $postulante !== null && ! $postulante->onboarding_completado;
        $this->pasoActual = min(5, max(1, $postulante?->onboarding_paso ?? 1));

        $user = auth()->user();
        $partesNombre = preg_split('/\s+/', trim($user->name), 2);
        $this->nombres = $user->nombres ?? ($partesNombre[0] ?? '');
        $this->apellidos = $user->apellidos ?? ($partesNombre[1] ?? '');
        $this->email = $user->email;
        $this->tipoDocumento = $postulante?->tipo_documento ?? 'rut';
        $this->rut = $this->tipoDocumento === 'rut'
            ? Rut::formatear($postulante?->rut ?? '')
            : ($postulante?->rut ?? '');
        $this->anioNacimiento = $postulante?->anio_nacimiento;
        $this->genero = $postulante?->genero ?? '';
        $this->nacionalidad = $postulante?->nacionalidad ?? 'Chilena';
        $this->titular = $postulante?->titular ?? '';
        $this->telefono = $postulante?->telefono ?? '';
        $this->linkedin = $postulante?->linkedin ?? '';
        $this->sitioWeb = $postulante?->sitio_web ?? '';
        $this->ciudad = $postulante?->ciudad ?? '';
        $this->regionesInteres = $postulante?->regiones_interes ?? [];
        $this->modalidadesTrabajo = $postulante?->modalidad_trabajo ?? [];
        $this->situacionLaboral = $postulante?->situacion_laboral ?? '';
        $this->expectativaRenta = $postulante?->expectativa_renta;
        $this->cargoActual = $postulante?->cargo_actual ?? '';
        $this->industriasInteres = $postulante?->industrias_interes ?? [];
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
        $this->aniosExperiencia = $postulante?->anios_experiencia;
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
        if ($this->tipoDocumento === 'rut') {
            $this->rut = Rut::formatear($this->rut);
        }

        $this->validateOnly('rut', $this->reglasDocumento());
    }

    public function updatedTipoDocumento(): void
    {
        if (! in_array($this->tipoDocumento, ['rut', 'pasaporte'], true)) {
            $this->tipoDocumento = 'rut';
        }

        if ($this->tipoDocumento === 'rut') {
            $this->rut = Rut::formatear($this->rut);
        }

        $this->resetValidation('rut');
    }

    /**
     * @return array<string, mixed>
     */
    private function reglasDocumento(): array
    {
        return [
            'tipoDocumento' => ['required', Rule::in(['rut', 'pasaporte'])],
            'rut' => $this->tipoDocumento === 'pasaporte'
                ? ['required', 'string', 'max:30']
                : ['required', 'string', 'max:20', new RutValido],
        ];
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

    public function anterior(): void
    {
        if (! $this->modoOnboarding || $this->pasoActual === 1) {
            return;
        }

        $this->pasoActual--;
        auth()->user()->postulante()->update(['onboarding_paso' => $this->pasoActual]);
        $this->resetValidation();
    }

    public function omitir(MatchingService $matching): void
    {
        if (! $this->modoOnboarding || $this->pasoActual !== 5) {
            return;
        }

        $this->completarOnboarding($matching);
    }

    public function avanzar(MatchingService $matching): void
    {
        if (! $this->modoOnboarding) {
            return;
        }

        $guardado = match ($this->pasoActual) {
            1 => $this->guardarDatosPersonales(),
            2 => $this->guardarExperiencias(),
            3 => $this->guardarEducaciones(),
            4 => $this->guardarIdiomas(),
            5 => $this->guardarCurriculum(),
        };

        if (! $guardado) {
            return;
        }

        if ($this->pasoActual < 5) {
            $this->continuarOnboarding();

            return;
        }

        $this->completarOnboarding($matching);
    }

    private function completarOnboarding(MatchingService $matching): void
    {
        $postulante = auth()->user()->postulante()->firstOrFail();
        $postulante->update([
            'onboarding_paso' => 5,
            'onboarding_completado' => true,
            'completitud' => max(100, $postulante->completitud),
        ]);

        $matching->sincronizarPostulante($postulante->fresh());
        $this->modoOnboarding = false;
        $this->redirectRoute('postulante.panel', navigate: true);
    }

    private function continuarOnboarding(): void
    {
        $this->pasoActual++;
        auth()->user()->postulante()->update(['onboarding_paso' => $this->pasoActual]);
        $this->resetValidation();
    }

    private function guardarDatosPersonales(): bool
    {
        if ($this->tipoDocumento === 'rut') {
            $this->rut = Rut::formatear($this->rut);
        }

        $validated = $this->validate($this->reglasDatos());

        DB::transaction(function () use ($validated): void {
            auth()->user()->update([
                'name' => trim($validated['nombres'].' '.$validated['apellidos']),
                'nombres' => $validated['nombres'],
                'apellidos' => $validated['apellidos'],
                'email' => $validated['email'],
            ]);
            auth()->user()->postulante()->update([
                ...$this->atributosDatos($validated),
                'completitud' => max(25, $this->completitud),
            ]);
        });

        $this->completitud = max(25, $this->completitud);

        return true;
    }

    /**
     * Campos del bloque "Datos": personales, contacto, acerca de mí e información adicional.
     *
     * @return array<string, mixed>
     */
    private function reglasDatos(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:50'],
            'apellidos' => ['required', 'string', 'max:50'],
            ...$this->reglasDocumento(),
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
            'telefono' => ['required', 'string', 'max:30'],
            'linkedin' => ['nullable', 'url:http,https', 'max:100'],
            'sitioWeb' => ['nullable', 'url:http,https', 'max:100'],
            'titular' => ['required', 'string', 'max:100'],
            'resumenProfesional' => ['nullable', 'string', 'max:900'],
            'regionesInteres' => ['array', 'max:5'],
            'regionesInteres.*' => [Rule::in(CatalogosProfesionales::regiones()), 'distinct:strict'],
            'industriasInteres' => ['required', 'array', 'min:1', 'max:5'],
            'industriasInteres.*' => [Rule::in(CatalogosProfesionales::industrias()), 'distinct:strict'],
            'modalidadesTrabajo' => ['array', 'max:'.count(CatalogosProfesionales::modalidadesTrabajoPreferidas())],
            'modalidadesTrabajo.*' => [Rule::in(CatalogosProfesionales::modalidadesTrabajoPreferidas()), 'distinct:strict'],
            'situacionLaboral' => ['nullable', Rule::in(CatalogosProfesionales::situacionesLaborales())],
            'expectativaRenta' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'nacionalidad' => ['required', Rule::in(CatalogosProfesionales::nacionalidades())],
            'anioNacimiento' => ['required', 'integer', 'min:1900', 'max:'.now()->year],
            'aniosExperiencia' => ['required', 'integer', 'min:0', 'max:80'],
            'genero' => ['required', Rule::in(CatalogosProfesionales::generos())],
            'ciudad' => ['required', Rule::in(CatalogosProfesionales::regiones())],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function atributosDatos(array $validated): array
    {
        return [
            'rut' => $validated['rut'],
            'tipo_documento' => $validated['tipoDocumento'],
            'telefono' => $validated['telefono'],
            'linkedin' => $validated['linkedin'],
            'sitio_web' => $validated['sitioWeb'],
            'titular' => $validated['titular'],
            'resumen_profesional' => $validated['resumenProfesional'],
            'regiones_interes' => array_values($validated['regionesInteres'] ?? []),
            'industrias_interes' => array_values($validated['industriasInteres'] ?? []),
            'modalidad_trabajo' => array_values($validated['modalidadesTrabajo'] ?? []),
            'situacion_laboral' => $validated['situacionLaboral'],
            'expectativa_renta' => $validated['expectativaRenta'],
            'nacionalidad' => $validated['nacionalidad'],
            'anio_nacimiento' => $validated['anioNacimiento'],
            'anios_experiencia' => $validated['aniosExperiencia'],
            'genero' => $validated['genero'],
            'ciudad' => $validated['ciudad'],
        ];
    }

    private function guardarExperiencias(): bool
    {
        $validated = $this->validate([
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
        ]);

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
            return false;
        }

        $principal = $validated['experiencias'][0];

        auth()->user()->postulante()->update([
            'cargo_actual' => $principal['cargo'],
            'empresa_actual' => $principal['empresa'],
            'experiencia_area' => $principal['actividad_empresa'],
            'experiencia_inicio' => $principal['inicio_anio'],
            'experiencia_fin' => $principal['fin_anio'],
            'experiencias' => $validated['experiencias'],
            'completitud' => max(50, $this->completitud),
        ]);

        $this->completitud = max(50, $this->completitud);

        return true;
    }

    private function guardarEducaciones(): bool
    {
        $validated = $this->validate([
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
        ]);

        foreach ($validated['educaciones'] as $index => $educacion) {
            if (in_array($educacion['nivel'], CatalogosProfesionales::nivelesEscolares(), true)) {
                if ($educacion['egreso_anio'] === null && ($educacion['situacion'] ?? null) !== 'Estudiando') {
                    $this->addError("educaciones.$index.egreso_anio", 'El año de egreso es obligatorio.');
                }

                $validated['educaciones'][$index] = array_replace($educacion, [
                    'carrera' => null,
                    'mencion' => null,
                    'modalidad' => null,
                    'inicio_anio' => null,
                    'termino_anio' => null,
                ]);

                continue;
            }

            foreach (['carrera', 'modalidad', 'situacion', 'inicio_anio', 'termino_anio'] as $campo) {
                if (blank($educacion[$campo])) {
                    $this->addError("educaciones.$index.$campo", 'Este campo es obligatorio para el nivel seleccionado.');
                }
            }

            if ($educacion['inicio_anio'] !== null && $educacion['termino_anio'] !== null && $educacion['termino_anio'] < $educacion['inicio_anio']) {
                $this->addError("educaciones.$index.termino_anio", 'El año de término debe ser posterior al de inicio.');
            }

            $validated['educaciones'][$index]['egreso_anio'] = null;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return false;
        }

        $principal = collect($validated['educaciones'])
            ->first(fn (array $educacion): bool => ! in_array($educacion['nivel'], CatalogosProfesionales::nivelesEscolares(), true))
            ?? $validated['educaciones'][0];

        auth()->user()->postulante()->update([
            'carrera' => $principal['carrera'],
            'universidad' => $principal['institucion'],
            'especialidad' => $principal['mencion'],
            'postgrado' => in_array($principal['nivel'], ['Postgrado', 'Magíster', 'Doctorado'], true) ? $principal['carrera'] : null,
            'educaciones' => $validated['educaciones'],
            'completitud' => max(75, $this->completitud),
        ]);

        $this->completitud = max(75, $this->completitud);

        return true;
    }

    private function guardarIdiomas(): bool
    {
        $validated = $this->validate([
            'idiomas' => ['required', 'array', 'min:1', 'max:'.count(CatalogosProfesionales::idiomas())],
            'idiomas.*.idioma' => ['required', Rule::in(CatalogosProfesionales::idiomas()), 'distinct:strict'],
            'idiomas.*.nivel' => ['required', Rule::in(CatalogosProfesionales::nivelesIdioma())],
        ]);

        auth()->user()->postulante()->update([
            'idiomas' => $validated['idiomas'],
            'completitud' => 100,
        ]);

        $this->completitud = 100;

        return true;
    }

    private function guardarCurriculum(): bool
    {
        $validated = $this->validate([
            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if (! isset($validated['cv']) || $validated['cv'] === null) {
            return true;
        }

        $rutaAnterior = auth()->user()->postulante?->cv_ruta;
        $rutaNueva = $validated['cv']->store('cvs', 'local');

        if ($rutaNueva === false) {
            $this->addError('cv', 'No pudimos guardar el archivo. Inténtalo nuevamente.');

            return false;
        }

        auth()->user()->postulante()->update(['cv_ruta' => $rutaNueva]);

        if ($rutaAnterior !== null) {
            Storage::disk('local')->delete($rutaAnterior);
        }

        $this->cvRutaExistente = $rutaNueva;
        $this->reset('cv');

        return true;
    }

    public function save(MatchingService $matching): void
    {
        if ($this->tipoDocumento === 'rut') {
            $this->rut = Rut::formatear($this->rut);
        }

        $validated = $this->validate([
            ...$this->reglasDatos(),
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
            'visible' => ['boolean'],
            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        foreach ($validated['educaciones'] as $index => $educacion) {
            $esEscolar = in_array($educacion['nivel'], CatalogosProfesionales::nivelesEscolares(), true);

            if ($esEscolar) {
                if ($educacion['egreso_anio'] === null && ($educacion['situacion'] ?? null) !== 'Estudiando') {
                    $this->addError("educaciones.$index.egreso_anio", 'El año de egreso es obligatorio.');
                }

                $validated['educaciones'][$index] = array_replace($educacion, [
                    'carrera' => null,
                    'mencion' => null,
                    'modalidad' => null,
                    'inicio_anio' => null,
                    'termino_anio' => null,
                ]);

                continue;
            }

            foreach (['carrera', 'modalidad', 'situacion', 'inicio_anio', 'termino_anio'] as $campo) {
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
                    'name' => trim($validated['nombres'].' '.$validated['apellidos']),
                    'nombres' => $validated['nombres'],
                    'apellidos' => $validated['apellidos'],
                    'email' => $validated['email'],
                ]);

                return Postulante::query()->updateOrCreate(['user_id' => auth()->id()], [
                    ...$this->atributosDatos($validated),
                    'cargo_actual' => $principal['cargo'],
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
        $this->cvRutaExistente = $postulante->cv_ruta;
        $this->reset('cv');

        session()->flash('status', 'Perfil profesional actualizado correctamente.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function calculateCompletitud(array $validated): int
    {
        $requiredFields = [
            'nombres',
            'email',
            'rut',
            'anioNacimiento',
            'industriasInteres',
            'educaciones',
            'idiomas',
            'experiencias',
        ];

        $completedFields = collect($requiredFields)
            ->filter(fn (string $field): bool => filled($validated[$field] ?? null))
            ->count();

        return (int) round(($completedFields / count($requiredFields)) * 100);
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

    #[Title('Mi perfil profesional · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.postulante.ficha', [
            'industrias' => CatalogosProfesionales::industrias(),
            'generos' => CatalogosProfesionales::generos(),
            'nacionalidades' => CatalogosProfesionales::nacionalidades(),
            'regiones' => CatalogosProfesionales::regiones(),
            'modalidadesTrabajoPreferidas' => CatalogosProfesionales::modalidadesTrabajoPreferidas(),
            'situacionesLaborales' => CatalogosProfesionales::situacionesLaborales(),
            'tiposTrabajo' => CatalogosProfesionales::tiposTrabajo(),
            'jerarquias' => CatalogosProfesionales::jerarquias(),
            'meses' => CatalogosProfesionales::meses(),
            'instituciones' => CatalogosProfesionales::instituciones(),
            'carrerasEstudio' => CatalogosProfesionales::carrerasEstudio(),
            'nivelesEstudio' => CatalogosProfesionales::nivelesEstudio(),
            'nivelesEscolares' => CatalogosProfesionales::nivelesEscolares(),
            'modalidadesEstudio' => CatalogosProfesionales::modalidadesEstudio(),
            'situacionesEstudio' => CatalogosProfesionales::situacionesEstudio(),
            'idiomasDisponibles' => CatalogosProfesionales::idiomas(),
            'nivelesIdioma' => CatalogosProfesionales::nivelesIdioma(),
        ]);
    }
}
