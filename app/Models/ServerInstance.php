<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class ServerInstance extends Model
{
    use HasFactory;
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'api_key_hash',
        'is_active',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'api_key_hash',
    ];

    /**
     * @return HasMany<Monitoring, $this>
     */
    public function monitorings(): HasMany
    {
        return $this->hasMany(Monitoring::class, 'preferred_location', 'code');
    }

    public function verifyApiKey(string $apiKey): bool
    {
        return Hash::check($apiKey, $this->api_key_hash);
    }

    /**
     * Scope a query to active server instances.
     */
    #[Scope]
    protected function active(Builder $builder): Builder
    {
        return $builder->where('is_active', true);
    }

    /**
     * Hash plain-text API keys before persisting.
     */
    protected function apiKeyHash(): Attribute
    {
        return Attribute::make(set: function (string $value) {
            return ['api_key_hash' => Hash::make($value)];
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
