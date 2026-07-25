<?php

namespace App\Livewire\Empresa;

use App\Models\Pago;
use App\Models\Plan;
use App\Services\FlowService;
use App\Services\ValorUf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

class Planes extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->role === 'empresa', 403);

        // Tras pagar, el siguiente paso del onboarding es completar los datos.
        $empresa = auth()->user()->empresa;

        if (($empresa?->planVigente() ?? false) && ! $empresa->datosEnviados()) {
            $this->redirectRoute('empresa.activacion', navigate: true);
        }
    }

    /** Inicia el pago de un plan en Flow y redirige a la pasarela. */
    public function contratar(int $planId): mixed
    {
        $empresa = auth()->user()->empresa;
        abort_unless($empresa !== null, 403);

        $plan = Plan::query()->where('audiencia', 'empresa')->find($planId);
        abort_if($plan === null, 404);

        // Monto en CLP a partir de la UF del día (+ IVA).
        try {
            $amount = $plan->precioClp(app(ValorUf::class)->actual());
        } catch (Throwable $e) {
            Log::error('Valor UF falló', ['error' => $e->getMessage()]);
            $this->addError('pago', 'No pudimos obtener el valor de la UF. Inténtalo nuevamente en unos minutos.');

            return null;
        }

        if ($amount < 350) {
            $this->addError('pago', 'El monto del plan no es válido para procesar el pago. Contáctanos.');

            return null;
        }

        $pago = Pago::query()->create([
            'empresa_id' => $empresa->id,
            'plan_id' => $plan->id,
            'commerce_order' => 'tmp',
            'amount' => $amount,
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

    #[Title('Planes · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $empresa = auth()->user()->empresa;

        // Los precios se muestran en UF; la conversión a CLP se hace al pagar (contratar).
        return view('livewire.empresa.planes', [
            'empresa' => $empresa,
            'planActual' => $empresa?->plan,
            'planVigente' => $empresa?->planVigente() ?? false,
            'planes' => Plan::query()->where('audiencia', 'empresa')->orderBy('precio_uf')->get(),
        ]);
    }
}
