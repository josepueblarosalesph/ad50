<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Exige un correo corporativo: rechaza los proveedores de correo gratuitos/personales
 * más comunes. Se usa en el registro de empresas.
 */
class EmailCorporativo implements ValidationRule
{
    /**
     * Dominios de correo gratuito/personal no permitidos para empresas.
     *
     * @var list<string>
     */
    private const DOMINIOS_BLOQUEADOS = [
        'gmail.com',
        'googlemail.com',
        'hotmail.com',
        'hotmail.es',
        'hotmail.cl',
        'outlook.com',
        'outlook.es',
        'outlook.cl',
        'live.com',
        'live.cl',
        'msn.com',
        'yahoo.com',
        'yahoo.es',
        'yahoo.cl',
        'ymail.com',
        'icloud.com',
        'me.com',
        'mac.com',
        'aol.com',
        'protonmail.com',
        'proton.me',
        'gmx.com',
        'zoho.com',
        'mail.com',
        'yandex.com',
    ];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $dominio = mb_strtolower(trim((string) mb_strstr($value, '@')));
        $dominio = ltrim($dominio, '@');

        if ($dominio === '') {
            return;
        }

        if (in_array($dominio, self::DOMINIOS_BLOQUEADOS, true)) {
            $fail('Usa el correo corporativo de tu empresa (no se permiten correos personales como Gmail, Hotmail u Outlook).');
        }
    }
}
