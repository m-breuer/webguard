<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'status_page_id',
    'name',
    'description',
    'position',
])]
class StatusPageComponent extends Model
{
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    /**
     * @return BelongsTo<StatusPage, $this>
     */
    public function statusPage(): BelongsTo
    {
        return $this->belongsTo(StatusPage::class);
    }

    /**
     * @return BelongsToMany<Monitoring, $this>
     */
    public function monitorings(): BelongsToMany
    {
        return $this->belongsToMany(Monitoring::class, 'status_page_component_monitoring')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
