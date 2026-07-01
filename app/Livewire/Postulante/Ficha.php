<?php

namespace App\Livewire\Postulante;

use App\Models\Postulante;
use App\Rules\RutValido;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use App\Support\Rut;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Ficha extends Component
{
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

    /** @var array<int, array{cargo: string, empresa: string, area: string, inicio: int|null, fin: int|null}> */
    public array $experiencias = [];

    public int $completitud = 0;

    public bool $visible = true;

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
        $this->empresaActual = $postulante?->empresa_actual ?? '';
        $this->experienciaArea = $postulante?->experiencia_area ?? '';
        $this->experienciaInicio = $postulante?->experiencia_inicio;
        $this->experienciaFin = $postulante?->experiencia_fin;
        $this->resumenProfesional = $postulante?->resumen_profesional ?? '';
        $this->aniosExperiencia = $postulante?->anios_experiencia ?? 0;
        $this->experiencias = $postulante?->experiencias ?: [[
            'cargo' => $postulante?->cargo_actual ?? '',
            'empresa' => $postulante?->empresa_actual ?? '',
            'area' => $postulante?->experiencia_area ?? '',
            'inicio' => $postulante?->experiencia_inicio,
            'fin' => $postulante?->experiencia_fin,
        ]];
        $this->completitud = $postulante?->completitud ?? 0;
        $this->visible = $postulante?->visible ?? true;
    }

    public function updatedCarrera(): void
    {
        $this->especialidad = '';
    }

    public function updatedRut(): void
    {
        $this->rut = Rut::formatear($this->rut);
        $this->validateOnly('rut', ['rut' => ['required', 'string', 'max:20', new RutValido]]);
    }

    public function addExperiencia(): void
    {
        if (count($this->experiencias) >= 3) {
            return;
        }

        $this->experiencias[] = ['cargo' => '', 'empresa' => '', 'area' => '', 'inicio' => null, 'fin' => null];
    }

    public function removeExperiencia(int $index): void
    {
        if ($index === 0 || ! isset($this->experiencias[$index])) {
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
            'carrera' => ['required', Rule::in(array_keys(CatalogosProfesionales::carreras()))],
            'universidad' => ['required', 'string', 'max:160'],
            'especialidad' => ['required', Rule::in(CatalogosProfesionales::especialidades($this->carrera))],
            'postgrado' => ['nullable', 'string', 'max:160'],
            'experiencias' => ['required', 'array', 'min:1', 'max:3'],
            'experiencias.*.cargo' => ['required', Rule::in(CatalogosProfesionales::cargosAreas())],
            'experiencias.*.empresa' => ['required', 'string', 'max:160'],
            'experiencias.*.area' => ['required', Rule::in(CatalogosProfesionales::cargosAreas())],
            'experiencias.*.inicio' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
            'experiencias.*.fin' => ['nullable', 'integer', 'min:1950', 'max:'.now()->year],
            'resumenProfesional' => ['nullable', 'string', 'max:2000'],
            'visible' => ['boolean'],
        ]);

        foreach ($validated['experiencias'] as $index => $experiencia) {
            if ($experiencia['fin'] !== null && $experiencia['fin'] < $experiencia['inicio']) {
                $this->addError("experiencias.$index.fin", 'El año de término debe ser posterior al de inicio.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $validated['aniosExperiencia'] = $this->calculateAniosExperiencia($validated['experiencias']);
        $principal = $validated['experiencias'][0];

        $completitud = $this->calculateCompletitud($validated);

        $postulante = DB::transaction(function () use ($validated, $completitud, $principal): Postulante {
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
                'carrera' => $validated['carrera'],
                'universidad' => $validated['universidad'],
                'especialidad' => $validated['especialidad'],
                'postgrado' => $validated['postgrado'],
                'empresa_actual' => $principal['empresa'],
                'experiencia_area' => $principal['area'],
                'experiencia_inicio' => $principal['inicio'],
                'experiencia_fin' => $principal['fin'],
                'experiencias' => $validated['experiencias'],
                'resumen_profesional' => $validated['resumenProfesional'],
                'anios_experiencia' => $validated['aniosExperiencia'],
                'visible' => $validated['visible'],
                'completitud' => $completitud,
            ]);
        });

        $matching->sincronizarPostulante($postulante);

        $this->completitud = $completitud;
        $this->aniosExperiencia = $validated['aniosExperiencia'];

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
            'carrera',
            'universidad',
            'especialidad',
            'experiencias',
        ];

        $completedFields = collect($requiredFields)
            ->filter(fn (string $field): bool => filled($validated[$field] ?? null))
            ->count();

        return (int) round(($completedFields / count($requiredFields)) * 100);
    }

    /**
     * @param  array<int, array{inicio: int, fin: int|null}>  $experiencias
     */
    private function calculateAniosExperiencia(array $experiencias): int
    {
        $intervalos = collect($experiencias)
            ->map(fn (array $experiencia): array => [
                (int) $experiencia['inicio'],
                (int) ($experiencia['fin'] ?? now()->year),
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

        return min(80, collect($fusionados)->sum(fn (array $intervalo): int => $intervalo[1] - $intervalo[0]));
    }

    #[Title('Mi ficha profesional · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.postulante.ficha', [
            'cargosAreas' => CatalogosProfesionales::cargosAreas(),
            'carreras' => array_keys(CatalogosProfesionales::carreras()),
            'especialidades' => CatalogosProfesionales::especialidades($this->carrera),
            'industrias' => CatalogosProfesionales::industrias(),
            'ciudades' => CatalogosProfesionales::ciudades(),
        ]);
    }
}
