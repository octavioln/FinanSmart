<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'description',
        'account_id',
        'category_id',
        'type',
        'effective_date',
        'amount'
    ];

    /**
     * Obtiene todos los registros para llenar la tabla del componente "Transactions"
     */
    public static function getAll($search, $sort, $direction)
    {
        return DB::table('transactions as t')
            ->select('t.*', 'a.name as account_name', 'c.name as category_name')
            ->join('accounts as a', 'a.id', '=', 't.account_id')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->orderBy('t.'.$sort, $direction)
            ->where(function ($query) use ($search) {
                $query->where('t.description', 'like', '%' .$search. '%')
                    ->orWhere('a.name', 'like', '%' .$search. '%')
                    ->orWhere('c.name', 'like', '%' .$search. '%');
            })
            ->paginate(10);
    }

    /**
     * Obtiene las últimas 10 transacciones que utiliza el componente Dashboard
     */
    public static function getMostRecent()
    {
        return DB::table('transactions as t')
            ->select('t.*', 'a.name as account_name', 'c.name as category_name')
            ->join('accounts as a', 'a.id', '=', 't.account_id')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->orderBy('t.id', 'DESC')
            ->paginate(5);
    }
}
