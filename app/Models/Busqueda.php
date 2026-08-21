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

    protected $casts = ['criterios' => 'json:unicode'];

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Usuario del equipo que creó la búsqueda. Queda en null si esa persona sale del
     * equipo: la búsqueda es de la empresa, no de quien la escribió.
     *
     * @return BelongsTo<User, $this>
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<BusquedaCandidato, $this> */
    public function candidatos(): HasMany
    {
        return $this->hasMany(BusquedaCandidato::class);
    }
}
