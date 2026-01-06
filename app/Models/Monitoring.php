<?php

namespace App\Models;

use App\Enums\HttpMethod;
use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use App\Enums\ServerInstance;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Override;

/**
 * Class Monitoring
 *
 * Represents a monitoring instance with type, target, keyword or port,
 * associated with a user and having many results.
 *
 **/
class Monitoring extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'monitorings';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'target',
        'port',
        'keyword',
        'status',
        'timeout',
        'http_method',
        'http_headers',
        'http_body',
        'auth_username',
        'auth_password',
        'public_label_enabled',
        'preferred_location',
        'email_notification_on_failure',
        'deleted_at',
        'maintenance_from',
        'maintenance_until',
    ];

    /**
     * Get the user that owns the monitoring.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MonitoringResponse, $this>
     */
    public function responseResults(): HasMany
    {
        return $this->hasMany(MonitoringResponse::class, 'monitoring_id');
    }

    /**
     * @return HasOne<MonitoringResponse, $this>
     */
    public function latestResponseResult(): HasOne
    {
        return $this->hasOne(MonitoringResponse::class, 'monitoring_id')->latestOfMany();
    }

    /**
     * @return HasMany<MonitoringDailyResult, $this>
     */
    public function dailyResults(): HasMany
    {
        return $this->hasMany(MonitoringDailyResult::class, 'monitoring_id');
    }

    /**
     * @return HasOne<MonitoringSslResult, $this>
     */
    public function sslResult(): HasOne
    {
        return $this->hasOne(MonitoringSslResult::class, 'monitoring_id');
    }

    /**
     * @return HasMany<Incident, $this>
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'monitoring_id');
    }

    /**
     * @return HasOne<Incident, $this>
     */
    public function latestIncident(): HasOne
    {
        return $this->hasOne(Incident::class, 'monitoring_id')->latestOfMany();
    }

    /**
     * @return HasMany<MonitoringNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(MonitoringNotification::class);
    }

    /**
     * @return HasMany<MonitoringResponseArchived, $this>
     */
    public function archivedResponseResults(): HasMany
    {
        return $this->hasMany(MonitoringResponseArchived::class, 'monitoring_id');
    }

    /**
     * Determine if the monitoring is active.
     */
    public function isActive(): bool
    {
        return $this->status === MonitoringLifecycleStatus::ACTIVE;
    }

    /**
     * Determine if the monitoring is paused.
     */
    public function isPaused(): bool
    {
        return $this->status === MonitoringLifecycleStatus::PAUSED;
    }

    /**
     * Determine if the monitoring is currently under maintenance.
     */
    public function isUnderMaintenance(): bool
    {
        if ($this->maintenance_from && is_null($this->maintenance_until)) {
            return $this->maintenance_from->isPast();
        }

        if ($this->maintenance_from && $this->maintenance_until) {
            return now()->between($this->maintenance_from, $this->maintenance_until);
        }

        return false;
    }

    /**
     * Apply the global scope to ensure all queries are restricted to the authenticated user.
     */
    #[Override]
    protected static function booted(): void
    {
        parent::boot();

        static::addGlobalScope('user', function (Builder $builder): void {
            if (Auth::check()) {
                $builder->where('user_id', Auth::user()->id);
            }
        });
    }

    /**
     * Scope a query to only include active monitorings.
     */
    #[Scope]
    protected function active(Builder $builder): Builder
    {
        return $builder->where('status', MonitoringLifecycleStatus::ACTIVE);
    }

    /**
     * Scope a query to only include paused monitorings.
     */
    #[Scope]
    protected function paused(Builder $builder): Builder
    {
        return $builder->where('status', MonitoringLifecycleStatus::PAUSED);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MonitoringType::class,
            'status' => MonitoringLifecycleStatus::class,
            'timeout' => 'integer',
            'http_method' => HttpMethod::class,
            'http_headers' => 'array',
            'public_label_enabled' => 'boolean',
            'email_notification_on_failure' => 'boolean',
            'preferred_location' => ServerInstance::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'maintenance_from' => 'datetime',
            'maintenance_until' => 'datetime',
        ];
    }
}
