<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\ElTicketsModel
 *
 * @property int $id
 * @property int $kilter
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $date_order
 * @property string $status
 * @property string $comment
 * @property int|null $change_id
 * @property string|null $date_change
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|ElTicketsModel newModelQuery()
 * @method static Builder|ElTicketsModel newQuery()
 * @method static Builder|ElTicketsModel query()
 * @method static Builder|ElTicketsModel whereChangeId($value)
 * @method static Builder|ElTicketsModel whereCreatedAt($value)
 * @method static Builder|ElTicketsModel whereDateChange($value)
 * @method static Builder|ElTicketsModel whereDateOrder($value)
 * @method static Builder|ElTicketsModel whereEmail($value)
 * @method static Builder|ElTicketsModel whereId($value)
 * @method static Builder|ElTicketsModel whereKilter($value)
 * @method static Builder|ElTicketsModel whereName($value)
 * @method static Builder|ElTicketsModel wherePhone($value)
 * @method static Builder|ElTicketsModel whereStatus($value)
 * @method static Builder|ElTicketsModel whereUpdatedAt($value)
 * @method static Builder|ElTicketsModel whereUuid($value)
 * @property string $city
 * @method static Builder|ElTicketsModel whereCity($value)
 * @method static Builder|ElTicketsModel whereComment($value)
 * @mixin Eloquent
 */
class ElTicketsModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'el_tickets';

    protected $fillable = [
        'kilter',
        'uuid',
        'name',
        'email',
        'phone',
        'comment',
        'date_order',
        'status',
        'change_id',
        'date_change',
    ];
}
