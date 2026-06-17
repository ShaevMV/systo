<?php

declare(strict_types=1);

namespace App\Models\EmailDelivery;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * Трекинг отправки письма (Ф2). Текущий статус письма; таймлайн — в domain_history.
 *
 * @property string $id
 * @property string $event
 * @property string $recipient
 * @property string|null $subject
 * @property string|null $template_slug
 * @property string $status
 * @property int $attempts
 * @property string|null $error
 * @property string $source
 * @property string|null $aggregate_type
 * @property string|null $aggregate_id
 * @property string|null $festival_id
 * @property string $tracking_token
 * @property string|null $provider_message_id
 * @property array|null $meta
 * @property string|null $mailable
 * @property Carbon|null $sent_at
 * @property Carbon|null $opened_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|EmailMessageModel query()
 * @method static Builder|EmailMessageModel whereId($value)
 * @mixin Eloquent
 */
class EmailMessageModel extends Model
{
    use HasUuid;

    public const TABLE = 'email_messages';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'event',
        'recipient',
        'subject',
        'template_slug',
        'status',
        'attempts',
        'error',
        'source',
        'aggregate_type',
        'aggregate_id',
        'festival_id',
        'tracking_token',
        'provider_message_id',
        'meta',
        'mailable',
        'sent_at',
        'opened_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'meta' => 'array',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
    ];
}
