<?php

namespace App\Rules;

use App\Support\Rut;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class RutValido implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Rut::esValido($value)) {
            $fail('El RUT ingresado no es válido. Revisa el número y su dígito verificador.');
        }
    }
}
