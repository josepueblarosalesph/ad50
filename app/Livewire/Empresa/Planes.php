<?php

namespace App\Livewire\Empresa;

use App\Models\Cupon;
use App\Models\Empresa;
use App\Models\Pago;
use App\Models\Plan;
use App\Services\FlowService;
use App\Services\ValorUf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

class Planes extends Component
{
    /** Cobro mínimo que acepta Flow. Por debajo de esto no hay pasarela que valga. */
    private const MONTO_MINIMO_FLOW = 350;

    /** Código que la empresa escribe en el campo de cupón. */
    public string $codigoCupon = '';

    /** Cupón ya validado y aplicado a la vista de precios. */
    public ?int $cuponAplicadoId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        // Tras pagar, el siguiente paso del onboarding es completar los datos.
        $empresa = auth()->user()->empresa;

        if (($empresa?->planVigente() ?? false) && ! $empresa->datosEnviados()) {
            $this->redirectRoute('empresa.activacion', navigate: true);
        }
    }

    /**
     * Valida el código escrito y lo deja aplicado sobre los precios.
     *
     * Aquí no se conoce todavía el plan, así que un cupón restringido a uno concreto se
     * acepta igual: la vista solo muestra el descuento en las tarjetas donde corresponde
     * y contratar() vuelve a comprobarlo antes de cobrar.
     */
    public function aplicarCupon(): void
    {
        $empresa = auth()->user()->empresa;
        abort_unless($empresa !== null, 403);

        $this->resetErrorBag('codigoCupon');

        $codigo = Cupon::normalizarCodigo($this->codigoCupon);

        if ($codigo === '') {
            $this->addError('codigoCupon', 'Escribe el código de tu cupón.');

            return;
        }

        $cupon = Cupon::query()->codigo($codigo)->first();

        if ($cupon === null) {
            // Mismo mensaje que un cupón caducado: el buscador de códigos ajenos no
            // necesita saber cuáles existen.
            $this->addError('codigoCupon', 'Ese cupón no existe o ya no está disponible.');

            return;
        }

        $motivo = $cupon->motivoRechazo(empresa: $empresa);

        if ($motivo !== null) {
            $this->addError('codigoCupon', $motivo);

            return;
        }

        $this->cuponAplicadoId = $cupon->id;
        $this->codigoCupon = $cupon->codigo;
    }

    public function quitarCupon(): void
    {
        $this->cuponAplicadoId = null;
        $this->codigoCupon = '';
        $this->resetErrorBag('codigoCupon');
    }

    /** Inicia el pago de un plan en Flow y redirige a la pasarela. */
    public function contratar(int $planId): mixed
    {
        $empresa = auth()->user()->empresa;
        abort_unless($empresa !== null, 403);

        $plan = Plan::query()->where('audiencia', 'empresa')->find($planId);
        abort_if($plan === null, 404);

        // El tope se comprueba aquí y no solo al pintar los botones: el id del plan llega
        // desde el cliente. Se cuentan los pagos confirmados, así que dejar un intento a
        // medias no gasta cupo.
        if (! $empresa->puedeContratar($plan)) {
            $liberacion = $empresa->proximaLiberacionDeCupo($plan);

            $this->addError('pago', $liberacion === null
                ? 'Ya alcanzaste el máximo de contrataciones de este plan.'
                : 'Ya contrataste el plan '.$plan->nombre.' '.$plan->max_contrataciones_anuales.' veces en los últimos 12 meses. Podrás volver a contratarlo el '.$liberacion->translatedFormat('j \d\e F \d\e Y').'.');

            return null;
        }

        // El cupón se revalida contra este plan concreto: entre aplicarlo y pulsar
        // Contratar pudo vencer, agotarse o resultar que era de otro plan.
        $cupon = null;

        if ($this->cuponAplicadoId !== null) {
            $cupon = Cupon::query()->find($this->cuponAplicadoId);

            $motivo = $cupon === null
                ? 'Ese cupón ya no está disponible.'
                : $cupon->motivoRechazo($plan, $empresa);

            if ($motivo !== null) {
                $this->cuponAplicadoId = null;
                $this->addError('codigoCupon', $motivo);

                return null;
            }
        }

        // Monto en CLP a partir de la UF del día (+ IVA).
        try {
            $bruto = $plan->precioClp(app(ValorUf::class)->actual());
        } catch (Throwable $e) {
            Log::error('Valor UF falló', ['error' => $e->getMessage()]);
            $this->addError('pago', 'No pudimos obtener el valor de la UF. Inténtalo nuevamente en unos minutos.');

            return null;
        }

        $descuento = $cupon?->descuentoSobre($bruto) ?? 0;
        $amount = $bruto - $descuento;

        if ($amount < self::MONTO_MINIMO_FLOW) {
            // Sin cupón, un monto así solo puede venir de un plan mal cargado.
            if ($cupon === null) {
                $this->addError('pago', 'El monto del plan no es válido para procesar el pago. Contáctanos.');

                return null;
            }

            return $this->contratarPorCortesia($empresa, $plan, $cupon, $bruto);
        }

        $pago = Pago::query()->create([
            'empresa_id' => $empresa->id,
            'plan_id' => $plan->id,
            'cupon_id' => $cupon?->id,
            'commerce_order' => 'tmp',
            'amount' => $amount,
            'descuento' => $descuento,
            'currency' => 'CLP',
            'estado' => 'pendiente',
        ]);
        $pago->update(['commerce_order' => 'AD50-'.$pago->id]);

        try {
            $flow = app(FlowService::class);
            $respuesta = $flow->crearPago([
                'commerceOrder' => $pago->commerce_order,
                'subject' => 'Plan '.$plan->nombre.' · AD+50',
                'amount' => $pago->amount,
                'email' => $empresa->contacto_principal_email ?: auth()->user()->email,
                'urlConfirmation' => route('pagos.flow.confirmar'),
                'urlReturn' => route('pagos.flow.retorno'),
            ]);

            $pago->update([
                'flow_token' => $respuesta['token'] ?? null,
                'flow_order' => $respuesta['flowOrder'] ?? null,
            ]);

            return redirect()->away($flow->urlRedireccion($respuesta));
        } catch (Throwable $e) {
            Log::error('Flow crearPago falló', ['pago' => $pago->id, 'error' => $e->getMessage()]);
            $pago->update(['estado' => 'error']);
            $this->addError('pago', 'No pudimos iniciar el pago. Inténtalo nuevamente en unos minutos.');

            return null;
        }
    }

    /**
     * El cupón cubre el precio entero: no hay nada que cobrar, así que no hay pasarela.
     *
     * Se deja igualmente un Pago en estado `pagado` con monto 0 y el cupón asociado. Es
     * el registro que hace de comprobante y, sobre todo, lo que cuenta el tope anual de
     * contrataciones (Empresa::contratacionesUltimoAnio): saltarse el Pago dejaría a las
     * cortesías fuera de ese conteo y el tope se podría burlar con un cupón del 100%.
     */
    private function contratarPorCortesia(Empresa $empresa, Plan $plan, Cupon $cupon, int $bruto): mixed
    {
        try {
            $pago = DB::transaction(function () use ($empresa, $plan, $cupon, $bruto): ?Pago {
                // El uso se anota antes de nada: si el cupón se agotó con otra
                // contratación simultánea, aquí no se regala el plan.
                if (! $cupon->registrarUso()) {
                    return null;
                }

                $pago = Pago::query()->create([
                    'empresa_id' => $empresa->id,
                    'plan_id' => $plan->id,
                    'cupon_id' => $cupon->id,
                    'commerce_order' => 'tmp',
                    'amount' => 0,
                    'descuento' => $bruto,
                    'currency' => 'CLP',
                    'estado' => 'pagado',
                    'pagado_at' => now(),
                ]);
                $pago->update(['commerce_order' => 'AD50-'.$pago->id]);

                $empresa->activarPlan($plan);

                return $pago;
            });
        } catch (Throwable $e) {
            Log::error('Cortesía por cupón falló', ['cupon' => $cupon->id, 'empresa' => $empresa->id, 'error' => $e->getMessage()]);
            $this->addError('pago', 'No pudimos activar tu plan. Inténtalo nuevamente en unos minutos.');

            return null;
        }

        if ($pago === null) {
            $this->cuponAplicadoId = null;
            $this->addError('codigoCupon', 'Ese cupón ya alcanzó su máximo de usos.');

            return null;
        }

        // Un plan entregado sin cobro: queda con qué cupón y a quién.
        Log::info('Plan activado por cupón de cortesía', [
            'pago' => $pago->id,
            'cupon' => $cupon->codigo,
            'empresa' => $empresa->id,
            'plan' => $plan->id,
            'precio_lista' => $bruto,
        ]);

        $ruta = $empresa->datosEnviados() ? 'empresa.panel' : 'empresa.activacion';

        return $this->redirectRoute($ruta, navigate: true);
    }

    #[Title('Planes · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $empresa = auth()->user()->empresa;

        // Los precios se muestran en UF; la conversión a CLP se hace al pagar (contratar).
        $planes = Plan::query()->where('audiencia', 'empresa')->orderBy('precio_uf')->get();

        $cupon = $this->cuponAplicadoId === null
            ? null
            : Cupon::query()->find($this->cuponAplicadoId);

        return view('livewire.empresa.planes', [
            'empresa' => $empresa,
            'planActual' => $empresa?->plan,
            'planVigente' => $empresa?->planVigente() ?? false,
            'planes' => $planes,
            'cupon' => $cupon,
            // Precio de lista, descuento y total en CLP por plan; vacío si no hay cupón.
            'preciosConCupon' => $this->preciosConCupon($planes, $cupon, $empresa),
            // Contrataciones que le quedan de cada plan con tope; NULL = sin tope.
            'restantesPorPlan' => $planes
                ->mapWithKeys(fn (Plan $plan): array => [$plan->id => $empresa?->contratacionesRestantes($plan)])
                ->all(),
            'liberacionPorPlan' => $planes
                ->mapWithKeys(fn (Plan $plan): array => [$plan->id => $empresa?->proximaLiberacionDeCupo($plan)])
                ->all(),
        ]);
    }

    /**
     * Desglose en CLP de cada plan al que alcanza el cupón.
     *
     * Solo se consulta el valor de la UF cuando hay un cupón aplicado: sin él la pantalla
     * anuncia precios en UF y no necesita la conversión, así que no se paga una llamada
     * externa en cada visita. Si la UF no responde, se devuelve vacío y la pantalla sigue
     * mostrando los precios de lista: el descuento se aplicará igual al contratar.
     *
     * @param  EloquentCollection<int, Plan>  $planes
     * @return array<int, array{bruto: int, descuento: int, final: int, cortesia: bool}>
     */
    private function preciosConCupon(EloquentCollection $planes, ?Cupon $cupon, ?Empresa $empresa): array
    {
        if ($cupon === null) {
            return [];
        }

        try {
            $valorUf = app(ValorUf::class)->actual();
        } catch (Throwable $e) {
            Log::warning('Valor UF falló al previsualizar un cupón', ['error' => $e->getMessage()]);

            return [];
        }

        $desglose = [];

        foreach ($planes as $plan) {
            if ($cupon->motivoRechazo($plan, $empresa) !== null) {
                continue;
            }

            $bruto = $plan->precioClp($valorUf);
            $descuento = $cupon->descuentoSobre($bruto);
            $final = $bruto - $descuento;

            $desglose[$plan->id] = [
                'bruto' => $bruto,
                'descuento' => $descuento,
                'final' => $final,
                'cortesia' => $final < self::MONTO_MINIMO_FLOW,
            ];
        }

        return $desglose;
    }
}
