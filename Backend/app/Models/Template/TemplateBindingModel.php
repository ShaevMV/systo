<?php

declare(strict_types=1);

namespace App\Models\Template;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * Привязка шаблонов к (festival_id, order_type, ticket_type_id) → email/pdf шаблоны + дефолт.
 *
 * @property string $id
 * @property string|null $festival_id
 * @property string|null $order_type
 * @property string|null $event
 * @property string|null $ticket_type_id
 * @property string|null $email_template_id
 * @property string|null $pdf_template_id
 * @property bool $is_default
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TemplateBindingModel query()
 * @method static Builder|TemplateBindingModel whereId($value)
 * @method static Builder|TemplateBindingModel whereActive($value)
 * @mixin Eloquent
 */
class TemplateBindingModel extends Model
{
    use HasUuid;

    public const TABLE = 'template_bindings';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'festival_id',
        'order_type',
        'event',
        'ticket_type_id',
        'types_of_payment_id',
        'email_template_id',
        'pdf_template_id',
        'is_default',
        'active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'active' => 'boolean',
    ];
}
