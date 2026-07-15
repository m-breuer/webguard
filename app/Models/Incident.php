<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegionalConsensusStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
#[Fillable([
    'monitoring_id',
    'consensus_status',
    'affected_locations',
    'problem_description',
    'resolution_description',
    'down_at',
    'up_at',

])]
class Incident extends Model
{
    use HasFactory;
    use HasUlids;

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
     * @return HasMany<IncidentUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class)->latest();
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consensus_status' => RegionalConsensusStatus::class,
            'affected_locations' => 'array',
            'problem_description' => 'string',
            'resolution_description' => 'string',
            'down_at' => 'datetime',
            'up_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
