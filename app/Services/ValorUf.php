<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Valor diario de la UF (Unidad de Fomento) en pesos, desde la API pública de
 * mindicador.cl. Se cachea por día para no consultar en cada carga.
 */
class ValorUf
{
    public function actual(): float
    {
        return Cache::remember('valor_uf.'.now()->toDateString(), now()->addDay(), function (): float {
            $valor = (float) Http::timeout(8)
                ->get('https://mindicador.cl/api/uf')
                ->throw()
                ->json('serie.0.valor', 0);

            if ($valor <= 0) {
                throw new \RuntimeException('No se pudo obtener el valor de la UF.');
            }

            return $valor;
        });
    }
}
