<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'amount'
    ];

    /**
     * Obtiene todos los registros para llenar la tabla del componente "Subscriptions"
     */
    public static function getAll($search, $sort, $direction)
    {
        return DB::table('subscriptions as s')
            ->select('s.*', 'a.name as account_name', 'c.name as category_name')
            ->join('accounts as a', 'a.id', '=', 's.account_id')
            ->join('categories as c', 'c.id', '=', 's.category_id')
            ->orderBy('s.'.$sort, $direction)
            ->where(function ($query) use ($search) {
                $query->where('s.name', 'like', '%' .$search. '%')
                    ->orWhere('a.name', 'like', '%' .$search. '%')
                    ->orWhere('c.name', 'like', '%' .$search. '%');
            })
            ->paginate(10);
    }

}
