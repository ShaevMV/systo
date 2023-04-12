<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
 * @method static Builder|FriendlyTicketModel whereName($value)
 * @method static Builder|FriendlyTicketModel whereProject($value)
 * @method static Builder|FriendlyTicketModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
class FriendlyTicketModel extends Model
{
    protected $table = 'friendly_tickets';

    protected $fillable = [
        'kilter',
        'project',
        'name',
        'email',
        'phone',
        'date_order',
        'change_id',
        'date_change',
    ];
}
