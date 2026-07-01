<?php

namespace App\Livewire\Empresa;

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
    public string $titulo = '';

    public string $cargo = '';

    public string $industria = '';

    public string $carrera = '';

    public string $especialidad = '';

    public string $ciudad = '';

    public string $palabraClave = '';

    public int $aniosMinimos = 0;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
    }

    public function updatedCarrera(): void
    {
        $this->especialidad = '';
    }

    public function save(MatchingService $matching): void
    {
        $validated = $this->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'cargo' => ['nullable', Rule::in(CatalogosProfesionales::cargosAreas())],
            'carrera' => ['nullable', Rule::in(array_keys(CatalogosProfesionales::carreras()))],
            'especialidad' => ['nullable', Rule::in(CatalogosProfesionales::especialidades($this->carrera))],
            'industria' => ['nullable', Rule::in(CatalogosProfesionales::industrias())],
            'ciudad' => ['nullable', Rule::in(CatalogosProfesionales::ciudades())],
            'palabraClave' => ['nullable', 'string', 'max:100'],
            'aniosMinimos' => ['required', 'integer', 'min:0', 'max:80'],
        ]);

        $busqueda = DB::transaction(function () use ($validated, $matching): Busqueda {
            $busqueda = Busqueda::query()->create([
                'empresa_id' => auth()->user()->empresa->id,
                'titulo' => $validated['titulo'],
                'rubro_oculto' => $validated['industria'] ?: null,
                'criterios' => [
                    'cargo' => $validated['cargo'],
                    'carrera' => $validated['carrera'],
                    'especialidad' => $validated['especialidad'],
                    'industria' => $validated['industria'],
                    'ciudad' => $validated['ciudad'],
                    'min_anios' => $validated['aniosMinimos'],
                    'palabra_clave' => $validated['palabraClave'],
                ],
                'estado' => 'activa',
            ]);

            $matching->sincronizar($busqueda);

            return $busqueda;
        });

        $this->redirectRoute('empresa.resultados', ['busqueda' => $busqueda], navigate: true);
    }

    #[Title('Nueva búsqueda · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.empresa.nueva-busqueda', [
            'cargosAreas' => CatalogosProfesionales::cargosAreas(),
            'carreras' => array_keys(CatalogosProfesionales::carreras()),
            'especialidades' => CatalogosProfesionales::especialidades($this->carrera),
            'industrias' => CatalogosProfesionales::industrias(),
            'ciudades' => CatalogosProfesionales::ciudades(),
        ]);
    }
}
