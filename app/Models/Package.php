<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Package
 *
 * Represents a subscription package with specific monitoring limits and pricing.
 *
 * @property string $id
 * @property int $monitoring_limit
 * @property float $price
 * @property bool $is_selectable
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, User> $users
 */
class Package extends Model
{
    use HasFactory, HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'monitoring_limit',
        'price',
        'is_selectable',
    ];

    /**
     * Get the users for the package.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_selectable' => 'boolean',
        ];
    }
}
