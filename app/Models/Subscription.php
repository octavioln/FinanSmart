<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Subscription extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'name',
        'account_id',
        'category_id',
        'payment_day',
        'amount',
    ];

    protected $casts = [
        'payment_day' => 'integer',
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
     * Obtiene todos los registros para llenar la tabla del componente "Subscriptions".
     */
    public static function getAll($search, $sort, $direction)
    {
        $sort = self::sortColumn($sort, [
            'id', 'name', 'account_id', 'category_id', 'payment_day', 'amount',
        ]);
        $direction = self::sortDirection($direction);
        $search = (string) $search;

        return DB::table('subscriptions as s')
            ->select('s.*', 'a.name as account_name', 'c.name as category_name')
            ->join('accounts as a', 'a.id', '=', 's.account_id')
            ->join('categories as c', 'c.id', '=', 's.category_id')
            ->orderBy('s.'.$sort, $direction)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereNull('c.deleted_at')
            ->where(function ($query) use ($search) {
                $query->where('s.name', 'like', '%'.$search.'%')
                    ->orWhere('a.name', 'like', '%'.$search.'%')
                    ->orWhere('c.name', 'like', '%'.$search.'%');
            })
            ->paginate(10);
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
