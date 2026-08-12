<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\FlowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class FlowController extends Controller
{
    public function __construct(private readonly FlowService $flow) {}

    /**
     * Webhook server-to-server de Flow. Nunca se confía en el cuerpo del POST: se
     * consulta el estado real con getStatus y, si está pagado, se activa el plan.
     * Idempotente: Flow puede reintentar varias veces.
     */
    public function confirmar(Request $request): Response
    {
        $token = (string) $request->input('token');

        if ($token === '') {
            return response('token requerido', 400);
        }

        try {
            $estado = $this->flow->estadoPago($token);
            $this->procesar($token, $estado);
        } catch (Throwable $e) {
            Log::error('Flow confirmar falló', ['token' => $token, 'error' => $e->getMessage()]);

            // 500 → Flow reintentará el webhook más tarde.
            return response('error', 500);
        }

        return response('', 200);
    }

    /**
     * Retorno del navegador tras pagar. La activación real la hace el webhook; aquí
     * solo se resuelve el mensaje a mostrar.
     */
    public function retorno(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token');

        try {
            $estado = $token !== '' ? $this->flow->estadoPago($token) : [];
            $pago = $token !== '' ? $this->procesar($token, $estado) : null;
        } catch (Throwable $e) {
            Log::error('Flow retorno falló', ['token' => $token, 'error' => $e->getMessage()]);
            $pago = null;
        }

        // Pago confirmado → completa los datos pendientes antes de entrar al panel.
        if ($pago?->estaPagado()) {
            $route = $pago->empresa->datosEnviados() ? 'empresa.panel' : 'empresa.activacion';

            return redirect()->route($route)->with('status', '¡Pago confirmado! Tu plan quedó activo.');
        }

        $mensaje = match ((int) ($estado['status'] ?? 0)) {
            3 => 'El pago fue rechazado. Puedes intentarlo nuevamente.',
            4 => 'El pago fue anulado.',
            default => 'Aún no confirmamos tu pago. Si lo realizaste, se activará en unos minutos.',
        };

        return redirect()->route('empresa.planes')->with('error_pago', $mensaje);
    }

    /**
     * Concilia el estado de Flow con el pago local y activa el plan si corresponde.
     *
     * @param  array<string, mixed>  $estado
     */
    private function procesar(string $token, array $estado): ?Pago
    {
        $commerceOrder = $estado['commerceOrder'] ?? null;

        $pago = Pago::query()
            ->when($commerceOrder !== null, fn ($q) => $q->where('commerce_order', $commerceOrder))
            ->when($commerceOrder === null, fn ($q) => $q->where('flow_token', $token))
            ->with('plan', 'empresa')
            ->first();

        if ($pago === null) {
            return null;
        }

        $status = (int) ($estado['status'] ?? 0);

        if ($status === 2 && ! $pago->estaPagado()) {
            $pago->update([
                'estado' => 'pagado',
                'pagado_at' => now(),
                'flow_order' => $estado['flowOrder'] ?? $pago->flow_order,
            ]);

            // Activa (o renueva) la suscripción de la empresa. El cupo se suma, no se
            // reemplaza: un plan de pago único se puede contratar varias veces y cada
            // compra aporta el suyo.
            $pago->empresa->activarPlan($pago->plan);
        } elseif ($status === 3 && $pago->estado === 'pendiente') {
            $pago->update(['estado' => 'rechazado']);
        } elseif ($status === 4 && $pago->estado === 'pendiente') {
            $pago->update(['estado' => 'anulado']);
        }

        return $pago->fresh();
    }
}
