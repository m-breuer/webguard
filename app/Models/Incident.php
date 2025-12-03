<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Incident
 *
 * Represents a monitoring incident, tracking downtime and uptime.
 *
 * @property string $id
 * @property string $monitoring_id
 * @property Carbon $down_at
 * @property Carbon|null $up_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Monitoring $monitoring
 */
class Incident extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'monitoring_id',
        'down_at',
        'up_at',

    ];

    /**
     * Get the monitoring that the incident belongs to.
     *
     * @return BelongsTo<Monitoring, $this>
     */
    public function monitoring(): BelongsTo
    {
        return $this->belongsTo(Monitoring::class);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'down_at' => 'datetime',
            'up_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
