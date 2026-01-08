<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationType;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringNotification extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $table = 'monitoring_notifications';

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
        'monitoring_id',
        'type',
        'message',
        'read',
        'sent',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['created_at_for_humans', 'translated_message'];

    /**
     * @return BelongsTo<Monitoring, $this>
     */
    public function monitoring(): BelongsTo
    {
        return $this->belongsTo(Monitoring::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new UserScope());
    }

    /**
     * Get the created_at attribute formatted for humans.
     */
    protected function createdAtForHumans(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->created_at->diffForHumans();
        });
    }

    /**
     * Get the translated message attribute.
     */
    protected function translatedMessage(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->type === NotificationType::STATUS_CHANGE) {
                $message = $this->message;
                $status = '';

                if (in_array($message, ['UP', 'DOWN'])) {
                    $status = mb_strtolower($message);
                } else {
                    // Handle old format for existing notifications
                    $status = mb_strtolower(str_replace('Monitoring status changed to ', '', $message));
                }

                return __('notifications.status_messages.' . $status, ['name' => $this->monitoring->name]);
            }

            // For other types, return the original message
            return $this->message;
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'read' => 'boolean',
            'sent' => 'boolean',
        ];
    }
}
