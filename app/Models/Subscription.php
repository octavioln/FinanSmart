<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Subscription extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PAUSED = 'paused';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_DUE_SOON = 'due_soon';

    public const STATUS_CURRENT = 'current';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Refleja el valor por defecto de la base de datos en instancias nuevas.
     * Sin esto, una suscripción recién creada se considera pausada hasta
     * volver a cargarse desde la base de datos.
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'account_id',
        'category_id',
        'payment_day',
        'amount',
        'is_active',
        'last_paid_at',
        'next_payment_date',
    ];

    protected $casts = [
        'payment_day' => 'integer',
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'last_paid_at' => 'date',
        'next_payment_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (self $subscription) {
            $subscription->refreshSchedule();
        });

        static::updated(function (self $subscription) {
            if ($subscription->wasChanged('payment_day')) {
                $subscription->refreshSchedule();
            }
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Recalcula el último pago y el próximo vencimiento a partir del historial.
     */
    public function refreshSchedule(): void
    {
        $latest = $this->latestPayment();

        $this->last_paid_at = $latest?->effective_date;
        $this->next_payment_date = $this->calculateNextPaymentDate($latest?->billing_period);

        if ($this->exists) {
            $this->saveQuietly();
        }
    }

    /**
     * Último pago registrado para la suscripción.
     */
    public function latestPayment(): ?Transaction
    {
        return $this->transactions()
            ->whereNotNull('billing_period')
            ->orderByDesc('billing_period')
            ->orderByDesc('effective_date')
            ->first();
    }

    /**
     * Periodo (Y-m) del último pago registrado.
     */
    public function latestPaymentPeriod(): ?string
    {
        $period = $this->transactions()
            ->whereNotNull('billing_period')
            ->max('billing_period');

        return $period ? (string) $period : null;
    }

    /**
     * Indica si la suscripción ya tiene un pago para el periodo indicado.
     */
    public function hasPaidPeriod(string $period): bool
    {
        return $this->transactions()->where('billing_period', $period)->exists();
    }

    /**
     * Periodo (Y-m) que corresponde al próximo pago pendiente.
     */
    public function billingPeriodForNextPayment(): string
    {
        $date = $this->next_payment_date
            ? $this->next_payment_date->copy()
            : $this->calculateNextPaymentDate($this->latestPaymentPeriod());

        return $date->format('Y-m');
    }

    /**
     * Calcula la próxima fecha de pago luego del periodo liquidado.
     */
    public function calculateNextPaymentDate(?string $lastPeriod): Carbon
    {
        if ($lastPeriod) {
            $month = Carbon::createFromFormat('Y-m-d', $lastPeriod.'-01')
                ->startOfDay()
                ->addMonth();

            return $this->dueDateForMonth($month);
        }

        $today = Carbon::today();
        $month = $today->copy()->startOfMonth();
        $due = $this->dueDateForMonth($month);

        return $due->lt($today)
            ? $this->dueDateForMonth($month->copy()->addMonth())
            : $due;
    }

    /**
     * Fecha de pago dentro de un mes, ajustando el día al último del mes.
     */
    public function dueDateForMonth(Carbon $month): Carbon
    {
        $month = $month->copy()->startOfMonth();
        $day = min(max((int) $this->payment_day, 1), $month->daysInMonth);

        return $month->day($day)->startOfDay();
    }

    /**
     * Estado de la suscripción según su pausa y su próximo vencimiento.
     */
    public function scheduleStatus(): string
    {
        if (! $this->is_active) {
            return self::STATUS_PAUSED;
        }

        if (! $this->next_payment_date) {
            return self::STATUS_CURRENT;
        }

        return self::statusForDate($this->next_payment_date);
    }

    /**
     * Etiqueta en español del estado de la suscripción.
     */
    public function scheduleStatusLabel(): string
    {
        return self::statusLabel($this->scheduleStatus());
    }

    /**
     * Estado a partir de una fila del listado (query builder).
     */
    public static function statusForRow(object $row): string
    {
        if (! $row->is_active) {
            return self::STATUS_PAUSED;
        }

        if (empty($row->next_payment_date)) {
            return self::STATUS_CURRENT;
        }

        return self::statusForDate(Carbon::parse($row->next_payment_date));
    }

    /**
     * Etiqueta en español para un estado.
     */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PAUSED => 'Pausada',
            self::STATUS_OVERDUE => 'Vencida',
            self::STATUS_DUE_SOON => 'Por vencer',
            default => 'Al día',
        };
    }

    /**
     * Compara una fecha de vencimiento con hoy.
     */
    protected static function statusForDate(Carbon $date): string
    {
        $date = $date->copy()->startOfDay();
        $today = Carbon::today();

        if ($date->lt($today)) {
            return self::STATUS_OVERDUE;
        }

        if ($date->lte($today->copy()->addDays(7))) {
            return self::STATUS_DUE_SOON;
        }

        return self::STATUS_CURRENT;
    }

    /**
     * Obtiene todos los registros para llenar la tabla del componente "Subscriptions".
     */
    public static function getAll($search, $sort, $direction, $filter = 'all')
    {
        $sort = self::sortColumn($sort, [
            'id', 'name', 'account_id', 'category_id', 'payment_day', 'amount',
            'is_active', 'last_paid_at', 'next_payment_date',
        ]);
        $direction = self::sortDirection($direction);
        $search = (string) $search;

        $results = DB::table('subscriptions as s')
            ->select('s.*', 'a.name as account_name', 'c.name as category_name')
            ->join('accounts as a', 'a.id', '=', 's.account_id')
            ->join('categories as c', 'c.id', '=', 's.category_id')
            ->orderBy('s.'.$sort, $direction)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereNull('c.deleted_at')
            ->when($filter === 'active', fn ($query) => $query->where('s.is_active', true))
            ->when($filter === 'inactive', fn ($query) => $query->where('s.is_active', false))
            ->where(function ($query) use ($search) {
                $query->where('s.name', 'like', '%'.$search.'%')
                    ->orWhere('a.name', 'like', '%'.$search.'%')
                    ->orWhere('c.name', 'like', '%'.$search.'%');
            })
            ->paginate(10);

        $results->getCollection()->transform(function ($row) {
            $row->status = self::statusForRow($row);
            $row->status_label = self::statusLabel($row->status);

            return $row;
        });

        return $results;
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
