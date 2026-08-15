<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Cupón de descuento sobre el precio de un plan, creado desde la administración.
 *
 * Todas las condiciones de un cupón viven aquí y se resuelven en un solo sitio
 * (motivoRechazo()), porque hay dos momentos que tienen que dar exactamente el mismo
 * veredicto: cuando la empresa lo escribe en la pantalla de planes y cuando pulsa
 * Contratar. Si cada uno hiciera sus propias comprobaciones, un cupón podría validarse
 * en pantalla y caerse al cobrar (o peor, al revés).
 *
 * El descuento se calcula siempre sobre el monto en CLP ya con IVA, que es lo que
 * efectivamente se cobra en Flow.
 *
 * @property int $id
 * @property string $codigo
 * @property string|null $descripcion
 * @property string $tipo
 * @property int $valor
 * @property int|null $plan_id
 * @property int|null $max_usos
 * @property int $usos
 * @property bool $uso_unico_por_empresa
 * @property Carbon|null $vigente_desde
 * @property Carbon|null $vigente_hasta
 * @property bool $activo
 * @property int|null $creado_por
 */
class Cupon extends Model
{
    protected $table = 'cupones';

    protected $guarded = [];

    /** Un cupón descuenta un porcentaje del precio o una cantidad fija de pesos. */
    public const TIPOS = [
        'porcentaje' => 'Porcentaje del precio',
        'monto' => 'Monto fijo en pesos',
    ];

    protected $casts = [
        'valor' => 'integer',
        'max_usos' => 'integer',
        'usos' => 'integer',
        'uso_unico_por_empresa' => 'boolean',
        'activo' => 'boolean',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    protected static function booted(): void
    {
        // El código es la llave con la que se busca: se normaliza al guardar para que
        // "verano25", " Verano25 " y "VERANO25" sean el mismo cupón y no tres.
        static::saving(function (Cupon $cupon): void {
            $cupon->codigo = self::normalizarCodigo($cupon->codigo);
        });
    }

    /** Mayúsculas y sin espacios: lo que se teclea nunca coincide letra por letra. */
    public static function normalizarCodigo(string $codigo): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', trim($codigo)) ?? '');
    }

    /** @param  Builder<Cupon>  $query */
    public function scopeCodigo(Builder $query, string $codigo): void
    {
        $query->where('codigo', self::normalizarCodigo($codigo));
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /** @return HasMany<Pago, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function esPorcentaje(): bool
    {
        return $this->tipo === 'porcentaje';
    }

    public function usosRestantes(): ?int
    {
        return $this->max_usos === null ? null : max(0, $this->max_usos - $this->usos);
    }

    public function estaAgotado(): bool
    {
        return $this->max_usos !== null && $this->usos >= $this->max_usos;
    }

    /** Dentro de la ventana de fechas (cualquiera de los dos extremos puede faltar). */
    public function enVentana(): bool
    {
        $hoy = now()->startOfDay();

        return ! ($this->vigente_desde !== null && $hoy->lt($this->vigente_desde->startOfDay()))
            && ! ($this->vigente_hasta !== null && $hoy->gt($this->vigente_hasta->startOfDay()));
    }

    /** Sirve hoy, para alguien: no mira empresa ni plan concretos. */
    public function estaVigente(): bool
    {
        return $this->activo && $this->enVentana() && ! $this->estaAgotado();
    }

    /**
     * Por qué este cupón no sirve para esta empresa y este plan, o null si sí sirve.
     *
     * Devuelve el mensaje ya redactado porque es lo que ve la empresa: el motivo real
     * (agotado, vencido, de otro plan) es información útil para quien lo teclea bien y
     * no revela nada que no supiera al recibir el código.
     *
     * El plan es opcional porque el cupón se escribe antes de elegirlo: sin plan se
     * comprueba todo lo demás y la restricción por plan queda para el momento de pagar.
     */
    public function motivoRechazo(?Plan $plan = null, ?Empresa $empresa = null): ?string
    {
        if (! $this->activo) {
            return 'Ese cupón ya no está disponible.';
        }

        if ($this->vigente_desde !== null && now()->startOfDay()->lt($this->vigente_desde->startOfDay())) {
            return 'Ese cupón empieza a regir el '.$this->vigente_desde->translatedFormat('j \d\e F \d\e Y').'.';
        }

        if ($this->vigente_hasta !== null && now()->startOfDay()->gt($this->vigente_hasta->startOfDay())) {
            return 'Ese cupón venció el '.$this->vigente_hasta->translatedFormat('j \d\e F \d\e Y').'.';
        }

        if ($this->estaAgotado()) {
            return 'Ese cupón ya alcanzó su máximo de usos.';
        }

        // Con plan_id no nulo la relación existe sí o sí: al borrar un plan la clave
        // queda en NULL (nullOnDelete), nunca apuntando a una fila que ya no está.
        if ($plan !== null && $this->plan_id !== null && $this->plan_id !== $plan->id) {
            return 'Ese cupón solo sirve para el plan '.$this->plan->nombre.'.';
        }

        if ($empresa !== null && $this->uso_unico_por_empresa && $this->yaLoUso($empresa)) {
            return 'Ya usaste ese cupón en una contratación anterior.';
        }

        return null;
    }

    /** La empresa ya lo gastó en un cobro confirmado. */
    public function yaLoUso(Empresa $empresa): bool
    {
        return $this->pagos()
            ->where('empresa_id', $empresa->id)
            ->where('estado', 'pagado')
            ->exists();
    }

    /**
     * Cuánto rebaja de un monto en CLP. Nunca más que el monto: un cupón de $50.000
     * sobre un plan de $30.000 lo deja en cero, no en negativo.
     */
    public function descuentoSobre(int $montoClp): int
    {
        $descuento = $this->esPorcentaje()
            ? (int) round($montoClp * min(100, $this->valor) / 100)
            : $this->valor;

        return max(0, min($descuento, $montoClp));
    }

    /** Lo que quedaría por cobrar tras aplicar el cupón. */
    public function montoFinal(int $montoClp): int
    {
        return $montoClp - $this->descuentoSobre($montoClp);
    }

    /**
     * Anota un uso sin pasarse del tope, aunque dos pagos se confirmen a la vez.
     *
     * La condición viaja dentro del UPDATE en lugar de leerse antes: comprobar `usos` en
     * PHP y escribir después deja una ventana en la que dos webhooks de Flow concurrentes
     * pasan los dos, y el último cupón se gastaría dos veces. Devuelve si el uso se anotó.
     */
    public function registrarUso(): bool
    {
        $anotado = static::query()
            ->whereKey($this->id)
            ->where(fn (Builder $q) => $q->whereNull('max_usos')->orWhereColumn('usos', '<', 'max_usos'))
            ->increment('usos');

        if ($anotado > 0) {
            $this->refresh();
        }

        return $anotado > 0;
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /** El descuento tal como se anuncia: «20%» o «$15.000». */
    public function valorLabel(): string
    {
        return $this->esPorcentaje()
            ? $this->valor.'%'
            : '$'.number_format($this->valor, 0, ',', '.');
    }
}
