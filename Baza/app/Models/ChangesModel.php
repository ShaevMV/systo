<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ChangesModel
 *
 * @property int $id
 * @property int $user_id
 * @property int $count_live_tickets
 * @property int $count_el_tickets
 * @property int $count_drug_tickets
 * @property int $count_spisok_tickets
 * @property string $start
 * @property string|null $end
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereCountDrugTickets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereCountElTickets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereCountLiveTickets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereCountSpisokTickets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesModel whereUserId($value)
 * @mixin \Eloquent
 */
class ChangesModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'changes';

    protected $fillable = [
        'user_id',
        'count_live_tickets',
        'count_el_tickets',
        'count_drug_tickets',
        'count_spisok_tickets',
        'start',
        'end',
    ];
}
