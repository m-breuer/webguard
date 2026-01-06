<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Class User
 *
 * Represents a registered user of the application, including authentication and related monitorings.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property UserRole $role
 * @property Carbon|null $terms_accepted_at
 * @property string|null $package_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read Collection<int, Monitoring> $monitorings
 * @property-read int|null $monitorings_count
 * @property-read Collection<int, ApiLog> $apiLogs
 * @property-read int|null $api_logs_count
 * @property-read Package|null $package
 *
 * @method static Builder|static newModelQuery()
 * @method static Builder|static newQuery()
 * @method static Builder|static query()
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasUlids;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'terms_accepted_at',
        'package_id',
        'locale',
        'theme',
        'github_id',
        'github_token',
        'github_refresh_token',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get all monitorings that belong to the user.
     *
     * @return HasMany<Monitoring, $this>
     */
    public function monitorings(): HasMany
    {
        return $this->hasMany(Monitoring::class);
    }

    /**
     * Get the API logs associated with the user.
     *
     * @return HasMany<ApiLog, $this>
     */
    public function apiLogs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    /**
     * Get the package associated with the user.
     *
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Determine if the user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Determine if the user has regular role.
     */
    public function isMember(): bool
    {
        return $this->role === UserRole::REGULAR;
    }

    /**
     * Determine if the user has guest role.
     */
    public function isGuest(): bool
    {
        return $this->role === UserRole::GUEST;
    }

    /**
     * Get all of the notifications for the user.
     *
     * @return HasManyThrough<MonitoringNotification, Monitoring, $this>
     */
    public function notifications(): HasManyThrough
    {
        return $this->hasManyThrough(MonitoringNotification::class, Monitoring::class);
    }

    /**
     * Get all of the unread notifications for the user.
     *
     * @return HasManyThrough<MonitoringNotification, Monitoring, $this>
     */
    public function unreadNotifications(): HasManyThrough
    {
        return $this->hasManyThrough(MonitoringNotification::class, Monitoring::class)->where('read', false);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'theme' => 'string',
        ];
    }
}
