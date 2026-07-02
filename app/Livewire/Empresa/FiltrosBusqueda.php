<?php

namespace App\Livewire\Empresa;

use App\Models\Busqueda;
use App\Services\MatchingService;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FiltrosBusqueda extends Component
{
    public Busqueda $busqueda;

    /** @var list<string> */
    public array $cargo = [];

    /** @var list<string> */
    public array $carrera = [];

    /** @var list<string> */
    public array $especialidad = [];

    /** @var list<string> */
    public array $industria = [];

    /** @var list<string> */
    public array $ciudad = [];

    public int $aniosMinimos = 0;

    public string $palabraClave = '';

    public function mount(Busqueda $busqueda): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);
        abort_unless($busqueda->empresa_id === auth()->user()->empresa?->id, 403);

        $this->busqueda = $busqueda;
        $criterios = $busqueda->criterios ?? [];
        $this->cargo = $this->normalizarSeleccion($criterios['cargo'] ?? []);
        $this->carrera = $this->normalizarSeleccion($criterios['carrera'] ?? []);
        $this->especialidad = $this->normalizarSeleccion($criterios['especialidad'] ?? []);
        $this->industria = $this->normalizarSeleccion($criterios['industria'] ?? []);
        $this->ciudad = $this->normalizarSeleccion($criterios['ciudad'] ?? []);
        $this->aniosMinimos = (int) ($criterios['min_anios'] ?? 0);
        $this->palabraClave = $criterios['palabra_clave'] ?? '';
    }

    public function updatedCarrera(): void
    {
        $this->especialidad = array_values(array_intersect($this->especialidad, $this->especialidadesDisponibles()));
    }

    public function guardar(MatchingService $matching): void
    {
        $validated = $this->validate([
            'cargo' => ['array'],
            'cargo.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::cargosAreas())],
            'carrera' => ['array'],
            'carrera.*' => ['string', 'distinct', Rule::in(array_keys(CatalogosProfesionales::carreras()))],
            'especialidad' => ['array'],
            'especialidad.*' => ['string', 'distinct', Rule::in($this->especialidadesDisponibles())],
            'industria' => ['array'],
            'industria.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::industrias())],
            'ciudad' => ['array'],
            'ciudad.*' => ['string', 'distinct', Rule::in(CatalogosProfesionales::ciudades())],
            'aniosMinimos' => ['required', 'integer', 'min:0', 'max:80'],
            'palabraClave' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $matching): void {
            $this->busqueda->update([
                'rubro_oculto' => $validated['industria'][0] ?? null,
                'criterios' => [
                    'cargo' => $validated['cargo'],
                    'carrera' => $validated['carrera'],
                    'especialidad' => $validated['especialidad'],
                    'industria' => $validated['industria'],
                    'ciudad' => $validated['ciudad'],
                    'min_anios' => $validated['aniosMinimos'],
                    'palabra_clave' => $validated['palabraClave'],
                ],
            ]);

            $matching->sincronizar($this->busqueda->fresh());
        });

        $this->dispatch('criterios-actualizados');
        session()->flash('status', 'Filtros actualizados.');
    }

    public function render(): View
    {
        return view('livewire.empresa.filtros-busqueda', [
            'grupos' => [
                ['Cargo', 'cargo', CatalogosProfesionales::cargosAreas()],
                ['Carrera', 'carrera', array_keys(CatalogosProfesionales::carreras())],
                ['Especialidad', 'especialidad', $this->especialidadesDisponibles()],
                ['Industria', 'industria', CatalogosProfesionales::industrias()],
                ['Ciudad', 'ciudad', CatalogosProfesionales::ciudades()],
            ],
        ]);
    }

    /** @return list<string> */
    private function normalizarSeleccion(mixed $valor): array
    {
        return collect((array) $valor)->filter(fn (mixed $item): bool => is_string($item) && filled($item))->values()->all();
    }

    /** @return list<string> */
    private function especialidadesDisponibles(): array
    {
        return collect($this->carrera)
            ->flatMap(fn (string $carrera): array => CatalogosProfesionales::especialidades($carrera))
            ->unique()->sort()->values()->all();
    }
}
