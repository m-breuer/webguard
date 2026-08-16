<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'status_page_subscription_id',
    'fingerprint',
    'sent_at',
])]
#[Table(name: 'status_page_maintenance_deliveries', key: 'id', keyType: 'string')]
#[WithoutIncrementing]
class StatusPageMaintenanceDelivery extends Model
{
    use HasUlids;

    /**
     * @return BelongsTo<StatusPageSubscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(StatusPageSubscription::class, 'status_page_subscription_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
