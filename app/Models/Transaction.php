<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_CARGO = 'Cargo';

    public const TYPE_ABONO = 'Abono';

    public const OPERATION_PURCHASE = 'purchase';

    public const OPERATION_WITHDRAWAL = 'withdrawal';

    public const OPERATION_DEPOSIT = 'deposit';

    public const OPERATION_SAVINGS = 'savings';

    public const OPERATION_TRANSFER = 'transfer';

    public const OPERATION_SUBSCRIPTION = 'subscription';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'description',
        'account_id',
        'category_id',
        'type',
        'effective_date',
        'amount',
        'transaction_group_id',
        'operation_type',
        'subscription_id',
        'billing_period',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $transaction) {
            if ($transaction->subscription_id) {
                if (blank($transaction->billing_period)) {
                    $date = $transaction->effective_date ?? Carbon::today();
                    $transaction->billing_period = Carbon::parse($date)->format('Y-m');
                }

                return;
            }

            $transaction->billing_period = null;
        });

        static::saved(fn (self $transaction) => $transaction->syncSubscriptionSchedule());
        static::forceDeleted(fn (self $transaction) => $transaction->syncSubscriptionSchedule());
        static::restored(fn (self $transaction) => $transaction->syncSubscriptionSchedule());

        static::deleted(function (self $transaction) {
            /* Libera el periodo para poder volver a registrar el pago si se elimina. */
            if ($transaction->billing_period !== null) {
                DB::table('transactions')
                    ->where('id', $transaction->getKey())
                    ->update(['billing_period' => null, 'updated_at' => now()]);

                $transaction->billing_period = null;
            }

            $transaction->syncSubscriptionSchedule();
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Recalcula el calendario de la suscripción vinculada al pago.
     */
    protected function syncSubscriptionSchedule(): void
    {
        if (! $this->subscription_id) {
            return;
        }

        Subscription::query()
            ->whereKey($this->subscription_id)
            ->first()
            ?->refreshSchedule();
    }

    /**
     * Obtiene todos los registros para llenar la tabla del componente "Transactions".
     */
    public static function getAll($search, $sort, $direction)
    {
        $sort = self::sortColumn($sort, [
            'id', 'effective_date', 'description', 'account_id', 'category_id', 'type', 'amount',
        ]);
        $direction = self::sortDirection($direction);
        $search = (string) $search;

        return DB::table('transactions as t')
            ->select('t.*', 'a.name as account_name', 'c.name as category_name')
            ->join('accounts as a', 'a.id', '=', 't.account_id')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->orderBy('t.'.$sort, $direction)
            ->whereNull('t.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereNull('c.deleted_at')
            ->where(function ($query) use ($search) {
                $query->where('t.description', 'like', '%'.$search.'%')
                    ->orWhere('a.name', 'like', '%'.$search.'%')
                    ->orWhere('c.name', 'like', '%'.$search.'%');
            })
            ->paginate(10);
    }

    /**
     * Obtiene las transacciones más recientes que utiliza el componente Dashboard.
     */
    public static function getMostRecent($sort = 'id', $direction = 'DESC')
    {
        $sort = self::sortColumn($sort, ['id', 'effective_date', 'category_id', 'amount']);
        $direction = self::sortDirection($direction);

        return DB::table('transactions as t')
            ->select('t.*', 'a.name as account_name', 'c.name as category_name')
            ->join('accounts as a', 'a.id', '=', 't.account_id')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->whereNull('t.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereNull('c.deleted_at')
            ->orderBy('t.'.$sort, $direction)
            ->paginate(5);
    }

    /**
     * Evita que un valor manipulado se use como columna de ordenamiento.
     */
    protected static function sortColumn($sort, array $allowed): string
    {
        return in_array($sort, $allowed, true) ? $sort : 'id';
    }

    /**
     * Normaliza la dirección de ordenamiento.
     */
    protected static function sortDirection($direction): string
    {
        return strtoupper((string) $direction) === 'ASC' ? 'ASC' : 'DESC';
    }
}
