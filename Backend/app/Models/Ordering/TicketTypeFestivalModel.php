<?php

declare(strict_types=1);

namespace App\Models\Ordering;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Ordering\TicketTypeFestivalModel
 *
 * @property int $id
 * @property string $festival_id
 * @property string $ticket_type_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $pdf
 * @property string $email
 * @property string|null $description
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel whereFestivalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel wherePdf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel whereTicketTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TicketTypeFestivalModel whereUpdatedAt($value)
 * @mixin \Eloquent
 */
final class TicketTypeFestivalModel  extends Model
{
    public const TABLE = 'ticket_type_festival';
    protected $table = self::TABLE;
}
