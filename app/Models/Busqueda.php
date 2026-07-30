<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Busqueda extends Model
{
    use SoftDeletes;

    /** Días que una búsqueda permanece en papelera antes de eliminarse en forma definitiva. */
    public const DIAS_RETENCION_PAPELERA = 30;

    /*
     * La búsqueda no tiene estado ni vigencia: es una configuración de filtros guardada
     * y siempre participa del matching. La etapa del proceso de selección (Long List,
     * Short List, Entrevistas…) vive en la publicación, que es donde se gestiona el
     * proceso. Para dejar de usar una búsqueda se elimina (queda en papelera).
     */

    protected $guarded = [];

    protected $casts = ['criterios' => 'array'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return HasMany<BusquedaCandidato, $this> */
    public function candidatos(): HasMany
    {
        return $this->hasMany(BusquedaCandidato::class);
    }
}
