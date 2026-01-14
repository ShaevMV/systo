<?php

declare(strict_types=1);

namespace App\Models\Ordering;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;


/**
 * App\Models\Tickets\Ordering\InviteModel
 *
 * @property string $id
 * @property string $order_id_list
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|CommentOrderTicketModel whereId($value)
 * @mixin Eloquent
 */
final class InviteModel extends Model
{
    use HasFactory, HasUuid;
    public const TABLE = 'invite';
    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'order_id_list'
    ];
}
