<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\FriendlyTicket
 *
 * @property int $id
 * @property int $kilter
 * @property string $project
 * @property string $email
 * @property string $name
 * @property string $date_order
 * @property string $status
 * @property string $comment
 * @property int|null $change_id
 * @property string|null $date_change
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|FriendlyTicketModel newModelQuery()
 * @method static Builder|FriendlyTicketModel newQuery()
 * @method static Builder|FriendlyTicketModel query()
 * @method static Builder|FriendlyTicketModel whereChangeId($value)
 * @method static Builder|FriendlyTicketModel whereCreatedAt($value)
 * @method static Builder|FriendlyTicketModel whereDateChange($value)
 * @method static Builder|FriendlyTicketModel whereDateOrder($value)
 * @method static Builder|FriendlyTicketModel whereEmail($value)
 * @method static Builder|FriendlyTicketModel whereId($value)
 * @method static Builder|FriendlyTicketModel whereKilter($value)
 * @method static Builder|FriendlyTicketModel whereFestivalId($value)
 * @method static Builder|FriendlyTicketModel whereName($value)
 * @method static Builder|FriendlyTicketModel whereProject($value)
 * @method static Builder|FriendlyTicketModel whereUpdatedAt($value)
 * @property string $seller
 * @method static Builder|FriendlyTicketModel whereComment($value)
 * @method static Builder|FriendlyTicketModel whereSeller($value)
 * @method static Builder|FriendlyTicketModel whereStatus($value)
 * @mixin Eloquent
 */
class FriendlyTicketModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'friendly_tickets';

    protected $fillable = [
        'kilter',
        'project',
        'name',
        'status',
        'email',
        'comment',
        'date_order',
        'change_id',
        'date_change',
    ];
}
