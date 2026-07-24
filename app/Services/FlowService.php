<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente de la API REST de Flow (https://www.flow.cl/docs/api.html).
 *
 * Firma HMAC-SHA256: se ordenan los parámetros por nombre ascendente, se concatenan
 * como nombre+valor (sin separadores) y se firma con la secretKey; el resultado en
 * hexadecimal se envía en el parámetro `s`.
 */
class FlowService
{
    private string $apiKey;

    private string $secretKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.flow.api_key');
        $this->secretKey = (string) config('services.flow.secret_key');
        $this->baseUrl = rtrim((string) config('services.flow.base_url'), '/');

        if ($this->apiKey === '' || $this->secretKey === '') {
            throw new RuntimeException('Faltan las credenciales de Flow (FLOW_API_KEY / FLOW_SECRET_KEY).');
        }
    }

    /**
     * Firma un conjunto de parámetros (sin incluir `s`).
     *
     * @param  array<string, scalar>  $params
     */
    public function firmar(array $params): string
    {
        ksort($params);

        $cadena = '';
        foreach ($params as $nombre => $valor) {
            $cadena .= $nombre.$valor;
        }

        return hash_hmac('sha256', $cadena, $this->secretKey);
    }

    /**
     * Crea una orden de pago. Devuelve el JSON de Flow (url, token, flowOrder).
     *
     * @param  array<string, scalar>  $datos  commerceOrder, subject, amount, email, urlConfirmation, urlReturn
     * @return array<string, mixed>
     */
    public function crearPago(array $datos): array
    {
        $params = array_merge([
            'apiKey' => $this->apiKey,
            'currency' => 'CLP',
            'paymentMethod' => 9, // todos los medios de pago
        ], $datos);

        $params = array_filter($params, fn ($valor): bool => $valor !== null && $valor !== '');
        $params['s'] = $this->firmar($params);

        return Http::asForm()
            ->post($this->baseUrl.'/payment/create', $params)
            ->throw()
            ->json();
    }

    /**
     * Consulta el estado de un pago por su token. Fuente de verdad para confirmar.
     *
     * @return array<string, mixed>
     */
    public function estadoPago(string $token): array
    {
        $params = ['apiKey' => $this->apiKey, 'token' => $token];
        $params['s'] = $this->firmar($params);

        return Http::get($this->baseUrl.'/payment/getStatus', $params)
            ->throw()
            ->json();
    }

    /** URL a la que se debe redirigir al comprador para pagar. */
    public function urlRedireccion(array $respuestaCreate): string
    {
        return $respuestaCreate['url'].'?token='.$respuestaCreate['token'];
    }
}
