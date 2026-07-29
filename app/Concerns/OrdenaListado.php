<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Ordenamiento por columna para los listados en tabla.
 *
 * El componente declara qué columnas se pueden ordenar y el encabezado
 * <x-th-ordenable> llama a ordenarPor(). El orden viaja en la URL, así que se
 * conserva al recargar y se puede compartir.
 */
trait OrdenaListado
{
    /** Columna por la que se ordena; vacío = la primera declarada. */
    #[Url(history: true)]
    public string $orden = '';

    /** Sentido del orden: asc | desc. */
    #[Url(history: true)]
    public string $direccion = '';

    /**
     * Columnas ordenables: clave visible => expresión por la que ordenar (columna de
     * la tabla, alias de withCount o ruta con punto para una relación ya cargada).
     *
     * @return array<string, string>
     */
    abstract protected function columnasOrdenables(): array;

    /**
     * Columnas que al elegirse parten descendentes. Son las de fecha y las de conteo,
     * donde lo útil es ver primero lo más reciente o lo más numeroso.
     *
     * @return list<string>
     */
    protected function columnasDescendentes(): array
    {
        return [];
    }

    /**
     * Orden inicial. Devolver cadena vacía deja el orden natural del componente (por
     * ejemplo `latest()`), que es lo que necesitan los paneles de "los más recientes":
     * ahí ordenar por defecto cambiaría el sentido del listado.
     */
    protected function ordenPorDefecto(): string
    {
        return (string) array_key_first($this->columnasOrdenables());
    }

    /**
     * Sentido ya acotado a los dos valores que acepta el query builder.
     *
     * @return 'asc'|'desc'
     */
    private function sentido(): string
    {
        return $this->direccion === 'asc' ? 'asc' : 'desc';
    }

    protected function direccionPorDefecto(): string
    {
        return in_array($this->ordenPorDefecto(), $this->columnasDescendentes(), true) ? 'desc' : 'asc';
    }

    /** Sanea lo que venga por URL. El componente lo llama desde su mount(). */
    protected function hidratarOrden(): void
    {
        if (! array_key_exists($this->orden, $this->columnasOrdenables())) {
            $this->orden = $this->ordenPorDefecto();
        }

        if (! in_array($this->direccion, ['asc', 'desc'], true)) {
            $this->direccion = $this->direccionPorDefecto();
        }
    }

    /**
     * Repetir la columna activa invierte el sentido; cambiar de columna parte en el
     * sentido natural de esa columna.
     */
    public function ordenarPor(string $campo): void
    {
        abort_unless(array_key_exists($campo, $this->columnasOrdenables()), 404);

        if ($this->orden === $campo) {
            $this->direccion = $this->direccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->orden = $campo;
            $this->direccion = in_array($campo, $this->columnasDescendentes(), true) ? 'desc' : 'asc';
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Aplica el orden a una consulta paginada o completa.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    protected function aplicarOrden(Builder $query): void
    {
        $this->hidratarOrden();

        if ($this->orden === '') {
            return;
        }

        $query
            ->orderBy($this->columnasOrdenables()[$this->orden], $this->sentido())
            // Desempate estable para que la paginación no baraje filas iguales.
            ->orderByDesc($query->getModel()->getQualifiedKeyName());
    }

    /**
     * Ordena una colección ya cargada. Es lo que usan los paneles que muestran los
     * últimos N registros: ordenar en la consulta cambiaría *cuáles* se muestran y el
     * listado dejaría de ser "los más recientes".
     *
     * @template TItem
     *
     * @param  Collection<int, TItem>  $items
     * @return Collection<int, TItem>
     */
    protected function ordenarColeccion(Collection $items): Collection
    {
        $this->hidratarOrden();

        if ($this->orden === '') {
            return $items;
        }

        $campo = $this->columnasOrdenables()[$this->orden];

        return $this->direccion === 'asc'
            ? $items->sortBy($campo)->values()
            : $items->sortByDesc($campo)->values();
    }
}
