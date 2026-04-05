<?php

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Questionnaire\QuestionnaireModel
 *
 * @property int $id
 * @property string|null $questionnaire_type_id
 * @property string $order_id
 * @property string|null $ticket_id Id билета
 * @property string|null $user_id Uuid пользователя
 * @property string $festival_id Фестиваль
 * @property string $status Статус по анкете
 * @property string|null $email email по которому будет создан пользователь
 * @property string|null $phone Телефон
 * @property string|null $telegram Telegram
 * @property array|null $data JSON с данными анкеты
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereFestivalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereTelegram($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereQuestionnaireTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class QuestionnaireModel extends Model
{
    use HasFactory;
    public const TABLE = 'questionnaire';
    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'ticket_id',
        'order_id',
        'phone',
        'telegram',
        'email',
        'user_id',
        'status',
        'festival_id',
        'data',
        'questionnaire_type_id',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
