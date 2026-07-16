<?php

namespace App\Livewire\Empresa;

use App\Services\DisponibilidadCandidatos;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Modelable;
use Livewire\Component;

/**
 * Combobox de selección múltiple con búsqueda en el servidor y conteo de candidatos
 * disponibles por opción. El catálogo NO se guarda como propiedad (se resuelve por
 * campo en cada request), así el snapshot de Livewire y la página quedan livianos.
 */
class SelectorCriterio extends Component
{
    /** @var list<string> */
    #[Modelable]
    public array $seleccion = [];

    public string $campo = '';

    public string $etiqueta = '';

    public string $descripcion = '';

    public string $buscar = '';

    public function agregar(string $opcion): void
    {
        if (in_array($opcion, $this->catalogo(), true) && ! in_array($opcion, $this->seleccion, true)) {
            $this->seleccion[] = $opcion;
        }

        $this->buscar = '';
    }

    public function quitar(string $opcion): void
    {
        $this->seleccion = array_values(array_filter($this->seleccion, fn (string $valor): bool => $valor !== $opcion));
    }

    /** @return list<string> */
    private function catalogo(): array
    {
        return match ($this->campo) {
            'cargo' => CatalogosProfesionales::cargos(),
            'carrera' => CatalogosProfesionales::carrerasEstudio(),
            'industria' => CatalogosProfesionales::industrias(),
            'ciudad' => CatalogosProfesionales::regionesInteres(),
            'habilidad' => CatalogosProfesionales::habilidades(),
            'situacion_laboral' => CatalogosProfesionales::situacionesLaborales(),
            'genero' => CatalogosProfesionales::generos(),
            'nivel_estudios' => CatalogosProfesionales::nivelesEstudio(),
            'situacion_estudios' => CatalogosProfesionales::situacionesEstudio(),
            'idioma' => CatalogosProfesionales::idiomas(),
            'actividad_economica' => CatalogosProfesionales::industrias(),
            default => [],
        };
    }

    private static function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }

    /**
     * Hasta 50 opciones que calzan con la búsqueda, anotadas con su conteo de candidatos.
     *
     * @return list<array{valor: string, total: int}>
     */
    private function resultados(DisponibilidadCandidatos $disponibilidad): array
    {
        $consulta = self::normalizar($this->buscar);
        $conteos = $disponibilidad->conteos($this->campo);

        return collect($this->catalogo())
            ->reject(fn (string $opcion): bool => in_array($opcion, $this->seleccion, true))
            ->filter(fn (string $opcion): bool => $consulta === '' || str_contains(self::normalizar($opcion), $consulta))
            ->take(50)
            ->map(fn (string $opcion): array => ['valor' => $opcion, 'total' => $conteos[$opcion] ?? 0])
            ->values()
            ->all();
    }

    public function render(DisponibilidadCandidatos $disponibilidad): View
    {
        return view('livewire.empresa.selector-criterio', [
            'resultados' => $this->resultados($disponibilidad),
        ]);
    }
}
