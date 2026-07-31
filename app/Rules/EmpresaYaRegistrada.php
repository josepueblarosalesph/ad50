<?php

namespace App\Rules;

use App\Models\Empresa;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Impide abrir una segunda cuenta para una empresa que ya está en la plataforma.
 *
 * Una empresa es una sola cuenta con varios usuarios (ver Empresa::usuarios()), no una
 * cuenta por persona. Como el correo corporativo ya es obligatorio en el registro de
 * empresas (ver EmailCorporativo), el dominio identifica de forma razonable a la
 * organización: si alguien de @ejemplo.cl intenta registrarse y ya existe una cuenta con
 * usuarios de @ejemplo.cl, el camino correcto es que el administrador de esa cuenta lo
 * sume desde Equipo, no crear una cuenta paralela.
 *
 * El mensaje nombra la empresa, pero no expone el correo de quien la administra: el
 * formulario ofrece un botón para enviarle la solicitud (ver Auth\Register::solicitarAcceso()),
 * así la persona sale del paso sin que publiquemos datos de contacto de terceros.
 */
class EmpresaYaRegistrada implements ValidationRule
{
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

        $dominio = self::dominioDe($value);

        if ($dominio === null) {
            return;
        }

        $empresa = self::empresaConDominio($dominio);

        if ($empresa === null) {
            return;
        }

        $fail(sprintf(
            'Ya existe una cuenta de %s registrada con el dominio @%s. Para sumarte a esa cuenta, solicita al administrador que te agregue como usuario desde la sección Equipo.',
            $empresa->razon_social,
            $dominio,
        ));
    }

    /** Empresa que ya tiene al menos un usuario con ese dominio de correo. */
    public static function empresaConDominio(string $dominio): ?Empresa
    {
        // `like` en vez de partir el correo en SQL: el patrón ancla el arroba, así que
        // @ejemplo.cl no calza con @otro-ejemplo.cl ni con @sub.ejemplo.cl.
        $patron = '%@'.addcslashes($dominio, '\\%_');

        return Empresa::query()
            ->whereHas('usuarios', fn ($query) => $query->whereRaw('lower(email) like ?', [$patron]))
            ->with('user:id,email')
            ->first();
    }

    /** Dominio del correo en minúsculas, o null si el valor no trae uno. */
    public static function dominioDe(string $email): ?string
    {
        $dominio = mb_strtolower(trim((string) mb_strstr($email, '@')));
        $dominio = ltrim($dominio, '@');

        return $dominio === '' ? null : $dominio;
    }
}
