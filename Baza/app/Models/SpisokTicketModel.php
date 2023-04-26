<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\SpisokTicket
 *
 * @property int $id
 * @property int $kilter
 * @property string $project
 * @property string $curator
 * @property string $email
 * @property string $name
 * @property string $date_order
 * @property int|null $change_id
 * @property string|null $date_change
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|SpisokTicketModel newModelQuery()
 * @method static Builder|SpisokTicketModel newQuery()
 * @method static Builder|SpisokTicketModel query()
 * @method static Builder|SpisokTicketModel whereChangeId($value)
 * @method static Builder|SpisokTicketModel whereCreatedAt($value)
 * @method static Builder|SpisokTicketModel whereCurator($value)
 * @method static Builder|SpisokTicketModel whereDateChange($value)
 * @method static Builder|SpisokTicketModel whereDateOrder($value)
 * @method static Builder|SpisokTicketModel whereEmail($value)
 * @method static Builder|SpisokTicketModel whereId($value)
 * @method static Builder|SpisokTicketModel whereKilter($value)
 * @method static Builder|SpisokTicketModel whereName($value)
 * @method static Builder|SpisokTicketModel whereProject($value)
 * @method static Builder|SpisokTicketModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
class SpisokTicketModel extends Model
{
    use SoftDeletes;

    protected $table = 'spisok_tickets';

    protected $fillable = [
        'kilter',
        'curator',
        'project',
        'name',
        'email',
        'phone',
        'date_order',
        'status',
        'change_id',
    ];
}
