<?php

namespace App\Livewire\Admin;

use App\Concerns\OrdenaListado;
use App\Models\Cupon;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cupones de descuento sobre el precio de los planes, para cualquier administrador.
 *
 * A diferencia de Planes (solo lectura: los precios se despliegan con el código), aquí
 * sí se escribe. La razón es que un cupón es una decisión comercial del día a día
 * —una campaña, un convenio, una cortesía para un piloto— y no puede depender de un
 * despliegue.
 *
 * Las condiciones del cupón (vigencia, tope de usos, plan al que aplica) las evalúa el
 * modelo Cupon, no esta pantalla: quien decide si un cupón sirve es el checkout, y tener
 * un único juez evita que la administración prometa algo que el cobro luego rechaza.
 *
 * Un cupón con usos ya cobrados no se elimina: es parte del historial de un pago. Se
 * desactiva, que es lo que corta su uso sin borrar de dónde salió un descuento.
 */
class Cupones extends Component
{
    use OrdenaListado;
    use WithPagination;

    #[Url(history: true)]
    public string $buscar = '';

    /** todos | vigentes | agotados | vencidos | inactivos */
    #[Url(history: true)]
    public string $estado = 'todos';

    /** Cupón que se está editando; null = se está creando uno nuevo. */
    public ?int $editandoId = null;

    public string $codigo = '';

    public string $descripcion = '';

    public string $tipo = 'porcentaje';

    public string $valor = '';

    /** '' = sirve para cualquier plan. */
    public string $planId = '';

    /** '' = sin tope de usos. */
    public string $maxUsos = '';

    public bool $usoUnicoPorEmpresa = true;

    public string $vigenteDesde = '';

    public string $vigenteHasta = '';

    public bool $activo = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        if (! in_array($this->estado, ['todos', 'vigentes', 'agotados', 'vencidos', 'inactivos'], true)) {
            $this->estado = 'todos';
        }

        $this->hidratarOrden();
    }

    public function updated(string $campo): void
    {
        if (in_array($campo, ['buscar', 'estado'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->estado = 'todos';
        $this->resetPage();
    }

    public function abrirNuevo(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $this->limpiarFormulario();
        $this->resetErrorBag();

        $this->modal('editar-cupon')->show();
    }

    public function abrirEdicion(int $cuponId): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $cupon = Cupon::query()->findOrFail($cuponId);

        $this->editandoId = $cupon->id;
        $this->codigo = $cupon->codigo;
        $this->descripcion = (string) $cupon->descripcion;
        $this->tipo = $cupon->tipo;
        $this->valor = (string) $cupon->valor;
        $this->planId = (string) ($cupon->plan_id ?? '');
        $this->maxUsos = (string) ($cupon->max_usos ?? '');
        $this->usoUnicoPorEmpresa = $cupon->uso_unico_por_empresa;
        $this->vigenteDesde = $cupon->vigente_desde?->toDateString() ?? '';
        $this->vigenteHasta = $cupon->vigente_hasta?->toDateString() ?? '';
        $this->activo = $cupon->activo;
        $this->resetErrorBag();

        $this->modal('editar-cupon')->show();
    }

    /** Propone un código legible y libre para no tener que inventarlo. */
    public function generarCodigo(): void
    {
        do {
            $codigo = 'AD50-'.Str::upper(Str::random(6));
        } while (Cupon::query()->codigo($codigo)->exists());

        $this->codigo = $codigo;
        $this->resetValidation('codigo');
    }

    public function guardar(): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $this->codigo = Cupon::normalizarCodigo($this->codigo);

        $validado = $this->validate([
            'codigo' => [
                'required', 'string', 'max:40', 'regex:/^[A-Z0-9\-_]+$/',
                Rule::unique('cupones', 'codigo')->ignore($this->editandoId),
            ],
            'descripcion' => ['nullable', 'string', 'max:160'],
            'tipo' => ['required', Rule::in(array_keys(Cupon::TIPOS))],
            // Un porcentaje no pasa de 100; un monto fijo se topa igual al precio del
            // plan cuando se aplica, así que aquí solo se exige que sea positivo.
            'valor' => ['required', 'integer', 'min:1', $this->tipo === 'porcentaje' ? 'max:100' : 'max:100000000'],
            'planId' => ['nullable', Rule::exists('planes', 'id')->where('audiencia', 'empresa')],
            'maxUsos' => ['nullable', 'integer', 'min:1'],
            'vigenteDesde' => ['nullable', 'date'],
            'vigenteHasta' => ['nullable', 'date', 'after_or_equal:vigenteDesde'],
        ], messages: [
            'codigo.regex' => 'El código solo admite letras, números, guiones y guiones bajos.',
            'valor.max' => $this->tipo === 'porcentaje'
                ? 'Un porcentaje de descuento no puede pasar de 100.'
                : 'El monto de descuento es demasiado alto.',
            'vigenteHasta.after_or_equal' => 'La fecha de término no puede ser anterior a la de inicio.',
        ], attributes: [
            'codigo' => 'código',
            'descripcion' => 'descripción',
            'valor' => 'valor del descuento',
            'planId' => 'plan',
            'maxUsos' => 'máximo de usos',
            'vigenteDesde' => 'vigencia desde',
            'vigenteHasta' => 'vigencia hasta',
        ]);

        $atributos = [
            'codigo' => $validado['codigo'],
            'descripcion' => $validado['descripcion'] !== '' ? $validado['descripcion'] : null,
            'tipo' => $validado['tipo'],
            'valor' => (int) $validado['valor'],
            'plan_id' => $validado['planId'] !== '' ? (int) $validado['planId'] : null,
            'max_usos' => $validado['maxUsos'] !== '' ? (int) $validado['maxUsos'] : null,
            'uso_unico_por_empresa' => $this->usoUnicoPorEmpresa,
            'vigente_desde' => $validado['vigenteDesde'] !== '' ? $validado['vigenteDesde'] : null,
            'vigente_hasta' => $validado['vigenteHasta'] !== '' ? $validado['vigenteHasta'] : null,
            'activo' => $this->activo,
        ];

        if ($this->editandoId !== null) {
            $cupon = Cupon::query()->findOrFail($this->editandoId);

            // Bajar el tope por debajo de lo ya cobrado dejaría el cupón en un estado
            // imposible (usos > max_usos): lo que corresponde ahí es desactivarlo.
            if ($atributos['max_usos'] !== null && $atributos['max_usos'] < $cupon->usos) {
                $this->addError('maxUsos', "Ese cupón ya se usó {$cupon->usos} veces. Para cortarlo, desactívalo.");

                return;
            }

            $cupon->update($atributos);
            $mensaje = "Cupón {$cupon->codigo} actualizado.";
        } else {
            $cupon = Cupon::query()->create($atributos + ['creado_por' => auth()->id()]);
            $mensaje = "Cupón {$cupon->codigo} creado.";
        }

        $this->cerrarFormulario();

        session()->flash('status', $mensaje);
    }

    /** Corta o reanuda el uso de un cupón sin tocar su historial. */
    public function alternarActivo(int $cuponId): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $cupon = Cupon::query()->findOrFail($cuponId);
        $cupon->update(['activo' => ! $cupon->activo]);

        session()->flash('status', $cupon->activo
            ? "Cupón {$cupon->codigo} activado."
            : "Cupón {$cupon->codigo} desactivado.");
    }

    /** Solo se borran los que nunca se usaron; el resto es historial de pagos. */
    public function eliminar(int $cuponId): void
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $cupon = Cupon::query()->findOrFail($cuponId);

        if ($cupon->usos > 0 || $cupon->pagos()->exists()) {
            session()->flash('status', "El cupón {$cupon->codigo} ya se usó, así que se desactivó en vez de borrarse.");
            $cupon->update(['activo' => false]);

            return;
        }

        $codigo = $cupon->codigo;
        $cupon->delete();

        session()->flash('status', "Cupón {$codigo} eliminado.");
    }

    private function cerrarFormulario(): void
    {
        $this->limpiarFormulario();
        $this->modal('editar-cupon')->close();
    }

    private function limpiarFormulario(): void
    {
        $this->reset(
            'editandoId', 'codigo', 'descripcion', 'tipo', 'valor', 'planId',
            'maxUsos', 'usoUnicoPorEmpresa', 'vigenteDesde', 'vigenteHasta', 'activo',
        );
    }

    /** @return array<string, string> */
    protected function columnasOrdenables(): array
    {
        return [
            'created_at' => 'cupones.created_at',
            'codigo' => 'cupones.codigo',
            'usos' => 'cupones.usos',
            'vigente_hasta' => 'cupones.vigente_hasta',
        ];
    }

    /** @return list<string> */
    protected function columnasDescendentes(): array
    {
        return ['created_at', 'usos'];
    }

    protected function ordenPorDefecto(): string
    {
        return 'created_at';
    }

    #[Title('Cupones · Administración AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $hoy = now()->toDateString();

        $query = Cupon::query()
            ->with('plan', 'creadoPor')
            ->when($this->buscar !== '', fn (Builder $q) => $q->where(
                fn (Builder $c) => $c->whereLike('codigo', '%'.Cupon::normalizarCodigo($this->buscar).'%')
                    ->orWhereLike('descripcion', '%'.$this->buscar.'%'),
            ))
            ->when($this->estado === 'inactivos', fn (Builder $q) => $q->where('activo', false))
            ->when($this->estado === 'agotados', fn (Builder $q) => $q
                ->whereNotNull('max_usos')
                ->whereColumn('usos', '>=', 'max_usos'))
            ->when($this->estado === 'vencidos', fn (Builder $q) => $q
                ->whereNotNull('vigente_hasta')
                ->whereDate('vigente_hasta', '<', $hoy))
            ->when($this->estado === 'vigentes', fn (Builder $q) => $q
                ->where('activo', true)
                ->where(fn (Builder $c) => $c->whereNull('max_usos')->orWhereColumn('usos', '<', 'max_usos'))
                ->where(fn (Builder $c) => $c->whereNull('vigente_desde')->orWhereDate('vigente_desde', '<=', $hoy))
                ->where(fn (Builder $c) => $c->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $hoy)))
            ->tap(fn (Builder $q) => $this->aplicarOrden($q));

        return view('livewire.admin.cupones', [
            'cupones' => $query->paginate(20),
            'totalCupones' => Cupon::query()->count(),
            'planes' => Plan::query()->where('audiencia', 'empresa')->orderBy('precio_uf')->get(),
            'hayFiltros' => $this->buscar !== '' || $this->estado !== 'todos',
        ]);
    }
}
