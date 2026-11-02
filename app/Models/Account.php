<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'name',
        'description',
        'available_amount',
        'opening_balance',
        'is_bank_account',
        'is_cash',
        'is_savings',
        'is_active',
        'allows_purchases',
        'allows_withdrawal',
        'allows_deposits',
        'allows_transfers',
    ];

    protected $casts = [
        'available_amount' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_bank_account' => 'boolean',
        'is_cash' => 'boolean',
        'is_savings' => 'boolean',
        'is_active' => 'boolean',
        'allows_purchases' => 'boolean',
        'allows_withdrawal' => 'boolean',
        'allows_deposits' => 'boolean',
        'allows_transfers' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Cuenta configurada para los retiros de efectivo.
     */
    public static function cash(bool $lock = false): ?self
    {
        $query = static::query()->where('is_cash', true)->where('is_active', true);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Cuenta configurada para los traspasos a ahorro.
     */
    public static function savings(bool $lock = false): ?self
    {
        $query = static::query()->where('is_savings', true)->where('is_active', true);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * Saldo que resulta de aplicar el saldo inicial y todos los movimientos.
     */
    public function calculatedBalance(): float
    {
        $net = $this->transactions()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'Abono' THEN amount ELSE -amount END), 0) as net")
            ->value('net');

        return round((float) $this->opening_balance + (float) $net, 2);
    }

    /**
     * Diferencia entre el saldo almacenado y el saldo calculado.
     */
    public function balanceDifference(): float
    {
        return round((float) $this->available_amount - $this->calculatedBalance(), 2);
    }

    /**
     * Actualiza el monto disponible en la cuenta.
     *
     * @param  string  $type  Tipo de transacción: "Cargo" o "Abono"
     * @param  float  $amount  Monto a actualizar
     */
    public function updateAvailableAmount($type, $amount): void
    {
        $this->applyTransactionEffect($type, $amount);
    }

    /**
     * Aplica (o revierte) el efecto de una transacción sobre el saldo.
     *
     * El saldo se actualiza de forma atómica en la base de datos para evitar
     * perder cambios cuando dos peticiones coinciden.
     */
    public function applyTransactionEffect($type, $amount, bool $reverse = false): void
    {
        $direction = match ($type) {
            'Cargo' => -1,
            'Abono' => 1,
            default => 0,
        };

        if ($direction === 0) {
            return;
        }

        if ($reverse) {
            $direction *= -1;
        }

        $delta = round($direction * (float) $amount, 2);

        if ($delta === 0.0) {
            return;
        }

        $value = number_format($delta, 2, '.', '');

        DB::table('accounts')
            ->where('id', $this->getKey())
            ->update([
                'available_amount' => DB::raw('available_amount + ('.$value.')'),
                'updated_at' => now(),
            ]);

        $this->refresh();
    }
}
