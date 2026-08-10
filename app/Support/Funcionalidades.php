<?php

namespace App\Support;

/**
 * Interruptores de las funcionalidades que aún no se publican (ver config/ad50.php).
 *
 * Existe para que el resto del código no ande repartiendo cadenas como
 * `config('ad50.funcionalidades.…')`, donde una errata se lee como "apagada" y nadie
 * se entera. Aquí un nombre mal escrito es un error de método.
 */
final class Funcionalidades
{
    /** Aviso y tarjeta con lo que le falta al postulante en su perfil. */
    public static function recomendacionesDePerfil(): bool
    {
        return (bool) config('ad50.funcionalidades.recomendaciones_perfil', false);
    }

    /** Carpetas para agrupar los favoritos de la empresa. */
    public static function carpetasDeFavoritos(): bool
    {
        return (bool) config('ad50.funcionalidades.carpetas_favoritos', false);
    }
}
