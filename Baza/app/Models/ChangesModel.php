<?php

namespace App\Models;

use Database\Factories\ChangesFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Nette\Utils\Json;

/**
 * App\Models\ChangesModel
 *
 * @property int $id
 * @property mixed $user_id
 * @property int $count_live_tickets
 * @property int $count_el_tickets
 * @property int $count_drug_tickets
 * @property int $count_spisok_tickets
 * @property string|null $start
 * @property string $festival_id
 * @property string|null $end
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|ChangesModel newModelQuery()
 * @method static Builder|ChangesModel newQuery()
 * @method static Builder|ChangesModel query()
 * @method static Builder|ChangesModel whereCountDrugTickets($value)
 * @method static Builder|ChangesModel whereCountElTickets($value)
 * @method static Builder|ChangesModel whereCountLiveTickets($value)
 * @method static Builder|ChangesModel whereCountSpisokTickets($value)
 * @method static Builder|ChangesModel whereCreatedAt($value)
 * @method static Builder|ChangesModel whereEnd($value)
 * @method static Builder|ChangesModel whereId($value)
 * @method static Builder|ChangesModel whereStart($value)
 * @method static Builder|ChangesModel whereUpdatedAt($value)
 * @method static Builder|ChangesModel whereUserId($value)
 * @mixin \Eloquent
 */
class ChangesModel extends Model
{
    use HasFactory;

    protected $table = self::TABLE;

    public const TABLE = 'changes';

    protected $fillable = [
        'user_id',
        'count_live_tickets',
        'count_el_tickets',
        'count_drug_tickets',
        'count_spisok_tickets',
        'count_parking_tickets',
        'count_parking_free_tickets',
        'count_parking_cross-country_tickets',
        'start',
        'end',
        'festival_id',
    ];

    /**
     * App\Models\ChangesModel живёт в namespace App\Models,
     * а Laravel ищет factory как Database\Factories\ChangesModelFactory
     * (по конвенции — имя модели + "Factory"). Наш файл называется
     * ChangesFactory (без суффикса Model), поэтому переопределяем.
     */
    protected static function newFactory(): ChangesFactory
    {
        return ChangesFactory::new();
    }
}
