<?php

namespace App\Livewire\Postulante;

use App\Models\Postulante;
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

    public int $completitud = 0;

    public bool $visible = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'postulante', 403);

        $postulante = auth()->user()->postulante;

        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
        $this->rut = $postulante?->rut ?? '';
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
        $this->completitud = $postulante?->completitud ?? 0;
        $this->visible = $postulante?->visible ?? true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
            'rut' => ['required', 'string', 'max:20'],
            'anioNacimiento' => ['required', 'integer', 'min:1900', 'max:'.now()->year],
            'telefono' => ['nullable', 'string', 'max:30'],
            'linkedin' => ['nullable', 'url:http,https', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:120'],
            'cargoActual' => ['required', 'string', 'max:160'],
            'industria' => ['required', 'string', 'max:120'],
            'industria2' => ['nullable', 'string', 'max:120'],
            'industria3' => ['nullable', 'string', 'max:120'],
            'carrera' => ['required', 'string', 'max:160'],
            'universidad' => ['required', 'string', 'max:160'],
            'especialidad' => ['required', 'string', 'max:160'],
            'postgrado' => ['nullable', 'string', 'max:160'],
            'empresaActual' => ['required', 'string', 'max:160'],
            'experienciaArea' => ['required', 'string', 'max:160'],
            'experienciaInicio' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
            'experienciaFin' => ['nullable', 'integer', 'min:1950', 'max:'.now()->year, 'gte:experienciaInicio'],
            'resumenProfesional' => ['nullable', 'string', 'max:2000'],
            'aniosExperiencia' => ['required', 'integer', 'min:0', 'max:80'],
            'visible' => ['boolean'],
        ]);

        $completitud = $this->calculateCompletitud($validated);

        DB::transaction(function () use ($validated, $completitud): void {
            auth()->user()->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            Postulante::query()->updateOrCreate(['user_id' => auth()->id()], [
                'rut' => $validated['rut'],
                'anio_nacimiento' => $validated['anioNacimiento'],
                'telefono' => $validated['telefono'],
                'linkedin' => $validated['linkedin'],
                'ciudad' => $validated['ciudad'],
                'cargo_actual' => $validated['cargoActual'],
                'industria' => $validated['industria'],
                'industria_2' => $validated['industria2'],
                'industria_3' => $validated['industria3'],
                'carrera' => $validated['carrera'],
                'universidad' => $validated['universidad'],
                'especialidad' => $validated['especialidad'],
                'postgrado' => $validated['postgrado'],
                'empresa_actual' => $validated['empresaActual'],
                'experiencia_area' => $validated['experienciaArea'],
                'experiencia_inicio' => $validated['experienciaInicio'],
                'experiencia_fin' => $validated['experienciaFin'],
                'resumen_profesional' => $validated['resumenProfesional'],
                'anios_experiencia' => $validated['aniosExperiencia'],
                'visible' => $validated['visible'],
                'completitud' => $completitud,
            ]);
        });

        $this->completitud = $completitud;

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
            'cargoActual',
            'industria',
            'carrera',
            'universidad',
            'especialidad',
            'empresaActual',
            'experienciaArea',
            'experienciaInicio',
        ];

        $completedFields = collect($requiredFields)
            ->filter(fn (string $field): bool => filled($validated[$field] ?? null))
            ->count();

        return (int) round(($completedFields / count($requiredFields)) * 100);
    }

    #[Title('Mi ficha profesional · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.postulante.ficha');
    }
}
