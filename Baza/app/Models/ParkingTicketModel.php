<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\ParkingTicketModel
 *
 * @property int $id
 * @property int $kilter
 * @property string $status
 * @property string $type
 * @property string $comment
 * @property int|null $change_id
 * @property string|null $date_change
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|ParkingTicketModel newModelQuery()
 * @method static Builder|ParkingTicketModel newQuery()
 * @method static Builder|ParkingTicketModel query()
 * @method static Builder|ParkingTicketModel whereChangeId($value)
 * @method static Builder|ParkingTicketModel whereCreatedAt($value)
 * @method static Builder|ParkingTicketModel whereDateChange($value)
 * @method static Builder|ParkingTicketModel whereId($value)
 * @method static Builder|ParkingTicketModel whereKilter($value)
 * @method static Builder|ParkingTicketModel whereType($value)
 * @method static Builder|ParkingTicketModel whereUpdatedAt($value)
 * @method static Builder|ParkingTicketModel whereComment($value)
 * @method static Builder|ParkingTicketModel whereStatus($value)
 * @mixin Eloquent
 */
class ParkingTicketModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'parking_tickets';

    protected $fillable = [
        'kilter',
        'type',
        'comment',
        'status',
        'change_id',
        'date_change',
    ];
}
