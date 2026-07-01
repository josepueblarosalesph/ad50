<?php

namespace App\Support;

final class Rut
{
    public static function formatear(string $rut): string
    {
        $rutLimpio = self::limpiar($rut);

        if (! preg_match('/^(\d{1,8})([0-9K])$/', $rutLimpio, $partes)) {
            return mb_strtoupper(trim($rut));
        }

        $cuerpo = number_format((int) $partes[1], 0, ',', '.');

        return $cuerpo.'-'.$partes[2];
    }

    public static function esValido(string $rut): bool
    {
        $rutLimpio = self::limpiar($rut);

        if (! preg_match('/^([1-9]\d{0,7})([0-9K])$/', $rutLimpio, $partes)) {
            return false;
        }

        return self::digitoVerificador($partes[1]) === $partes[2];
    }

    private static function limpiar(string $rut): string
    {
        return mb_strtoupper((string) preg_replace('/[.\-\s]/', '', trim($rut)));
    }

    private static function digitoVerificador(string $cuerpo): string
    {
        $suma = 0;
        $multiplicador = 2;

        for ($indice = strlen($cuerpo) - 1; $indice >= 0; $indice--) {
            $suma += (int) $cuerpo[$indice] * $multiplicador;
            $multiplicador = $multiplicador === 7 ? 2 : $multiplicador + 1;
        }

        return match (11 - ($suma % 11)) {
            11 => '0',
            10 => 'K',
            default => (string) (11 - ($suma % 11)),
        };
    }
}
