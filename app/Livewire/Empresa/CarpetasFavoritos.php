<?php

namespace App\Livewire\Empresa;

use App\Concerns\OrganizaFavoritosEnCarpetas;
use App\Models\Empresa;
use App\Models\Favorito;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Panel de carpetas de la barra lateral de Favoritos.
 *
 * Es un componente propio y no un parcial por una razón de fondo: el layout pinta el
 * slot `sidebar` dentro de su `<aside>`, o sea FUERA de la raíz del componente que lo
 * declara. Un `wire:click` puesto ahí queda huérfano —Livewire solo enlaza lo que cuelga
 * de su propia raíz— y no hace nada. Por eso las demás pantallas también montan un
 * componente anidado en la barra lateral (ver [FiltrosBusqueda], [FiltrosPostulaciones]).
 *
 * La comunicación con el listado va por eventos: este panel avisa qué carpeta se eligió
 * y cuándo cambió alguna, y el listado le avisa cuando una asignación movió los conteos.
 */
class CarpetasFavoritos extends Component
{
    use OrganizaFavoritosEnCarpetas;

    /** Carpeta activa, espejo de la del listado: 'todas', 'sin' o un id. */
    public string $activa = 'todas';

    public function mount(string $activa = 'todas'): void
    {
        abort_unless(auth()->user()->esEmpresa(), 403);

        $this->activa = $activa;
    }

    /** Cambia la carpeta activa y se lo comunica al listado. */
    public function verCarpeta(string $carpeta): void
    {
        abort_unless($this->carpetasHabilitadas(), 404);
        abort_unless(
            in_array($carpeta, ['todas', 'sin'], true) || $this->carpetasDelUsuario()->contains('id', (int) $carpeta),
            404,
        );

        $this->activa = $carpeta;
        $this->dispatch('carpeta-seleccionada', carpeta: $carpeta);
    }

    /**
     * El popup de crear lo monta el listado, no este panel: el panel se pinta dos veces
     * (barra lateral y plegable de móvil) y habría dos modales con el mismo nombre.
     */
    public function abrirNuevaCarpeta(): void
    {
        abort_unless($this->carpetasHabilitadas(), 404);

        $this->dispatch('abrir-nueva-carpeta');
    }

    /** El listado asignó o quitó carpetas a alguien: hay que rehacer los conteos. */
    #[On('refrescar-carpetas')]
    public function refrescar(): void
    {
        $this->olvidarCarpetas();
    }

    /** Si se borró la carpeta que se estaba viendo, el listado vuelve a mostrarlo todo. */
    protected function carpetaEliminada(int $carpetaId): void
    {
        if ($this->activa === (string) $carpetaId) {
            $this->activa = 'todas';
            $this->dispatch('carpeta-seleccionada', carpeta: 'todas');
        }

        $this->avisarCambio();
    }

    protected function empresaDeCarpetas(): ?Empresa
    {
        return auth()->user()->empresa;
    }

    /** El panel no asigna candidatos —eso vive en el listado—, pero el trait lo exige. */
    protected function favoritoDeCandidato(int $postulanteId): ?Favorito
    {
        $empresa = $this->empresaDeCarpetas();

        if ($empresa === null) {
            return null;
        }

        return Favorito::query()
            ->where('empresa_id', $empresa->id)
            ->where('postulante_id', $postulanteId)
            ->first();
    }

    /** Los nombres y contadores cambiaron: el listado repinta sus etiquetas. */
    protected function avisarCambio(): void
    {
        $this->dispatch('carpetas-cambiaron');
    }

    public function render(): View
    {
        return view('livewire.empresa.carpetas-favoritos', [
            'carpetas' => $this->carpetasDelUsuario(),
            // Los dos contadores fijos del panel. Se calculan aquí y no llegan del
            // listado porque este componente se pinta por su cuenta en la barra lateral.
            'totalFavoritos' => $this->favoritosVisibles()->count(),
            'sinCarpeta' => $this->favoritosVisibles()
                ->whereDoesntHave('carpetas', fn (Builder $q) => $q->where('carpetas_favoritos.user_id', auth()->id()))
                ->count(),
        ]);
    }

    /**
     * Favoritos de la empresa sobre perfiles visibles: la misma base que cuenta el
     * listado, para que los números del panel no prometan de más.
     *
     * @return Builder<Favorito>
     */
    private function favoritosVisibles(): Builder
    {
        $empresa = $this->empresaDeCarpetas();

        return Favorito::query()
            ->where('empresa_id', $empresa?->id)
            ->whereHas('postulante', fn (Builder $query) => $query->where('visible', true));
    }
}
