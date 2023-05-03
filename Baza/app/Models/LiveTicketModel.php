<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\LiveTicketModel
 *
 * @property int $id
 * @property int $kilter
 * @property string $status
 * @property string $comment
 * @property int|null $change_id
 * @property string|null $date_change
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|LiveTicketModel newModelQuery()
 * @method static Builder|LiveTicketModel newQuery()
 * @method static Builder|LiveTicketModel query()
 * @method static Builder|LiveTicketModel whereChangeId($value)
 * @method static Builder|LiveTicketModel whereCreatedAt($value)
 * @method static Builder|LiveTicketModel whereDateChange($value)
 * @method static Builder|LiveTicketModel whereId($value)
 * @method static Builder|LiveTicketModel whereKilter($value)
 * @method static Builder|LiveTicketModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
class LiveTicketModel extends Model
{
    protected $table = 'live_tickets';

    protected $fillable = [
        'kilter',
        'comment',
        'status',
        'change_id',
        'date_change',
    ];
}
