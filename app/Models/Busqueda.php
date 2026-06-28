<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Busqueda extends Model
{
    protected $guarded = [];
    protected $casts = ['criterios' => 'array'];

    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class); }
    public function candidatos(): HasMany { return $this->hasMany(BusquedaCandidato::class); }
}
