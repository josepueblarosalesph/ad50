<?php

namespace App\Models;

use App\Support\CatalogosProfesionales;
use Illuminate\Database\Eloquent\Model;

/**
 * Un valor de un catálogo administrable (una industria, un cargo, una región…).
 *
 * Al guardar o borrar se descarta la caché de lectura para que los formularios y el
 * motor de calce vean el cambio de inmediato.
 */
class TerminoCatalogo extends Model
{
    protected $table = 'terminos_catalogo';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => CatalogosProfesionales::olvidar());
        static::deleted(fn () => CatalogosProfesionales::olvidar());
    }
}
