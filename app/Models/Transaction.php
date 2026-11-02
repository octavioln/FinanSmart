<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    ];

    protected $casts = [
        'effective_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
