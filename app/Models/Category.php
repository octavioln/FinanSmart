<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const SYSTEM_SAVINGS = 'savings';

    public const SYSTEM_TRANSFER = 'transfer';

    public const SYSTEM_DEPOSIT = 'deposit';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $fillable = [
        'name',
        'description',
        'system_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
     * Categorías que se pueden elegir en una compra o suscripción.
     */
    public function scopeUsable($query)
    {
        return $query->where('is_active', true)->whereNull('system_key');
    }

    public function scopeWithSystemKey($query, string $key)
    {
        return $query->where('system_key', $key);
    }

    public static function system(string $key): ?self
    {
        return static::query()->withSystemKey($key)->first();
    }

    public function isSystem(): bool
    {
        return $this->system_key !== null;
    }
}
