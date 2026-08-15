<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'descuento' => 'integer',
        'flow_order' => 'integer',
        'pagado_at' => 'datetime',
    ];

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return BelongsTo<Cupon, $this> */
    public function cupon(): BelongsTo
    {
        return $this->belongsTo(Cupon::class);
    }

    /** Precio de lista antes del cupón: `amount` es siempre lo que se cobró. */
    public function montoBruto(): int
    {
        return $this->amount + (int) $this->descuento;
    }

    /** El cupón cubrió el total, así que no hubo cobro que hacer en la pasarela. */
    public function esCortesia(): bool
    {
        return $this->amount === 0 && (int) $this->descuento > 0;
    }

    public function estaPagado(): bool
    {
        return $this->estado === 'pagado';
    }
}
