<?php

namespace App\Livewire\Empresa;

use App\Services\DisponibilidadCandidatos;
use App\Support\CatalogosProfesionales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Combobox de selección múltiple con búsqueda en el servidor y conteo de candidatos por
 * opción. El catálogo NO se guarda como propiedad (se resuelve por campo en cada
 * request), así el snapshot de Livewire y la página quedan livianos.
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

    /**
     * Criterios actuales de la búsqueda completa. Hacen que el conteo por opción sea
     * contextual ("cuántas quedan si agrego esto") en vez de global.
     *
     * El padre los pasa como parámetro al montar, pero Livewire NO re-envía parámetros a
     * un hijo ya montado: los cambios posteriores llegan por el evento de más abajo.
     *
     * @var array<string, mixed>
     */
    public array $criterios = [];

    /**
     * Cada vez que el panel de filtros cambia un criterio anuncia el mapa completo.
     * Sin esto, el conteo se quedaría congelado en los criterios del montaje.
     *
     * @param  array<string, mixed>  $criterios
     */
    #[On('criterios-previsualizados')]
    public function sincronizarCriterios(array $criterios): void
    {
        $this->criterios = $criterios;
    }

    public function agregar(string $opcion): void
    {
        if (in_array($opcion, $this->catalogo(), true) && ! in_array($opcion, $this->seleccion, true)) {
            $this->seleccion[] = $opcion;
        }

        $this->buscar = '';
        $this->notificarCambio();
    }

    public function quitar(string $opcion): void
    {
        $this->seleccion = array_values(array_filter($this->seleccion, fn (string $valor): bool => $valor !== $opcion));
        $this->notificarCambio();
    }

    /**
     * Avisa al componente padre que la selección cambió. El binding wire:model no dispara el
     * hook updated() del padre, así que sin esto los filtros no se guardarían al tocar los tags.
     */
    private function notificarCambio(): void
    {
        $this->dispatch('criterio-actualizado', campo: $this->campo, valores: $this->seleccion);
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
            'idioma' => CatalogosProfesionales::idiomasConNivel(),
            'actividad_economica' => CatalogosProfesionales::industrias(),
            default => [],
        };
    }

    private static function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }

    /**
     * Hasta 50 opciones que calzan con el texto buscado, anotadas con cuántas fichas
     * quedarían al agregar esa opción a los criterios actuales.
     *
     * @return list<array{valor: string, total: int}>
     */
    private function resultados(DisponibilidadCandidatos $disponibilidad): array
    {
        $consulta = self::normalizar($this->buscar);
        $conteos = $disponibilidad->conteos($this->campo, $this->criterios);

        return collect($this->catalogo())
            ->reject(fn (string $opcion): bool => in_array($opcion, $this->seleccion, true))
            ->filter(fn (string $opcion): bool => $consulta === '' || str_contains(self::normalizar($opcion), $consulta))
            ->take(50)
            ->map(fn (string $opcion): array => [
                'valor' => $opcion,
                'total' => $conteos[DisponibilidadCandidatos::clave($opcion)] ?? 0,
            ])
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
