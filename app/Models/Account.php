<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory;
    use SoftDeletes;

    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'name',
        'description',
        'available_amount',
        'is_bank_account',
        'allows_purchases',
        'allows_withdrawal',
        'allows_deposits',
        'allows_transfers',
    ];

    /**
     * Actualiza el monto disponible en la cuenta
     * @param string $type: Tipo de transacción
     * @param float $amount: Monto a actualizar
     */
    public function updateAvailableAmount($type, $amount): void
    {
        switch ($type) {
            case 'Cargo':
                $this->available_amount -= $amount;
                $this->save();
                break;
            case 'Abono':
                $this->available_amount += $amount;
                $this->save();
                break;
            default:
                break;
        }
    }
}
