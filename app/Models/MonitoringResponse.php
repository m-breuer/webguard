<?php

namespace App\Models;

use App\Enums\MonitoringStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

/**
 * Class MonitoringResponse
 *
 * Represents a monitoring response result, tracking status and response time.
 *
 * @property string $id
 * @property string $monitoring_id
 * @property MonitoringStatus $status
 * @property float $response_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Monitoring $monitoring
 * @property-read User $user
 */
class MonitoringResponse extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * Indicates whether IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'monitoring_response_results';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'monitoring_id',
        'status',
        'response_time',
    ];

    /**
     * Get the monitoring instance that this result belongs to.
     *
     * @return BelongsTo<Monitoring, $this>
     */
    public function monitoring(): BelongsTo
    {
        return $this->belongsTo(Monitoring::class);
    }

    /**
     * Get the user associated with this monitoring result through monitoring.
     *
     * @return HasOneThrough<User, Monitoring, $this>
     */
    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Monitoring::class,
            'id',
            'id',
            'monitoring_id',
            'user_id'
        );
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MonitoringStatus::class,
            'response_time' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
