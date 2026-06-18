<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HttpMethod;
use App\Enums\MonitoringLifecycleStatus;
use App\Enums\MonitoringType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Override;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Class Monitoring
 *
 * Represents a monitoring instance with type, target, keyword or port,
 * associated with a user and having many results.
 *
 **/
#[Fillable([
    'user_id',
    'name',
    'type',
    'target',
    'port',
    'keyword',
    'dns_record_type',
    'dns_expected_values',
    'status',
    'timeout',
    'http_method',
    'expected_http_statuses',
    'http_headers',
    'http_body',
    'auth_username',
    'auth_password',
    'public_label_enabled',
    'preferred_location',
    'notification_on_failure',
    'notification_channels',
    'failure_confirmation_threshold',
    'ssl_expiry_warning_days',
    'deleted_at',
    'maintenance_from',
    'maintenance_until',
    'heartbeat_token',
    'heartbeat_interval_minutes',
    'heartbeat_grace_minutes',
    'heartbeat_last_ping_at',
    'server_health_token',
    'server_health_last_reported_at',
    'server_health_cpu_threshold_percent',
    'server_health_ram_threshold_percent',
    'server_health_storage_threshold_percent',
])]
#[Table(name: 'monitorings', key: 'id', keyType: 'string')]
#[WithoutIncrementing]
class Monitoring extends Model
{
    use HasFactory;
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

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
     * @return HasOne<MonitoringDomainResult, $this>
     */
    public function domainResult(): HasOne
    {
        return $this->hasOne(MonitoringDomainResult::class, 'monitoring_id');
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
     * @return HasOne<MonitoringNotification, $this>
     */
    public function latestStatusChangeNotification(): HasOne
    {
        return $this->hasOne(MonitoringNotification::class, 'monitoring_id')->ofMany(
            ['created_at' => 'max', 'id' => 'max'],
            function (Builder $builder): void {
                $builder->statusChange();
            }
        );
    }

    /**
     * @return HasOne<MonitoringNotification, $this>
     */
    public function latestUnreadStatusChangeNotification(): HasOne
    {
        return $this->hasOne(MonitoringNotification::class, 'monitoring_id')->ofMany(
            ['created_at' => 'max', 'id' => 'max'],
            function (Builder $builder): void {
                $builder->statusChange()->unread();
            }
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('monitoring')
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->dontLogIfAttributesChangedOnly([
                'heartbeat_last_ping_at',
                'server_health_last_reported_at',
                'updated_at',
            ])
            ->setDescriptionForEvent(fn (string $eventName): string => 'monitoring_' . $eventName);
    }

    /**
     * @return HasMany<MonitoringNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(MonitoringNotification::class);
    }

    /**
     * @return HasMany<StatusPageSubscriber, $this>
     */
    public function statusPageSubscribers(): HasMany
    {
        return $this->hasMany(StatusPageSubscriber::class);
    }

    /**
     * @return HasMany<MonitoringResponseArchived, $this>
     */
    public function archivedResponseResults(): HasMany
    {
        return $this->hasMany(MonitoringResponseArchived::class, 'monitoring_id');
    }

    /**
     * @return BelongsToMany<StatusPageComponent, $this>
     */
    public function statusPageComponents(): BelongsToMany
    {
        return $this->belongsToMany(StatusPageComponent::class, 'status_page_component_monitoring')
            ->withPivot('position')
            ->withTimestamps();
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

    public function isHeartbeat(): bool
    {
        return $this->type === MonitoringType::HEARTBEAT;
    }

    public function isServerHealth(): bool
    {
        return $this->type === MonitoringType::SERVER_HEALTH;
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
            'expected_http_statuses' => 'string',
            'dns_expected_values' => 'array',
            'http_headers' => 'array',
            'public_label_enabled' => 'boolean',
            'notification_on_failure' => 'boolean',
            'preferred_location' => 'string',
            'heartbeat_interval_minutes' => 'integer',
            'heartbeat_grace_minutes' => 'integer',
            'notification_channels' => 'array',
            'failure_confirmation_threshold' => 'integer',
            'ssl_expiry_warning_days' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'maintenance_from' => 'datetime',
            'maintenance_until' => 'datetime',
            'heartbeat_last_ping_at' => 'datetime',
            'server_health_last_reported_at' => 'datetime',
            'server_health_cpu_threshold_percent' => 'float',
            'server_health_ram_threshold_percent' => 'float',
            'server_health_storage_threshold_percent' => 'float',
        ];
    }
}
