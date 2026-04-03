<?php

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Questionnaire\QuestionnaireModel
 *
 * @property int $id
 * @property string $order_id
 * @property string|null $ticket_id Id билета
 * @property string|null $user_id Uuid пользователя
 * @property string $festival_id Фестиваль
 * @property string $status Статус по анкете
 * @property string $name имя на билете
 * @property string|null $email email по которому будет создан пользователь
 * @property int $agy Возраст
 * @property int $howManyTimes Сколько раз ты уже бывал на Систо
 * @property string $questionForSysto Ответь кратко и честно на простой вопрос Зачем ты едешь на Систо?
 * @property string|null $activeOfEvent Готовы принимать более активное или творческое участие в создании события
 * @property string|null $creationOfSisto Считаете ли вы себя участвующим в сотворении Систо
 * @property string|null $whereSysto Откуда ты узнал о Систо
 * @property string|null $telegram Telegram
 * @property string $phone Телефон
 * @property string|null $vk Вконтакте
 * @property string|null $musicStyles Стили музыки, которые предпочитаешь в лесу
 * @property int $is_have_in_club Хочет участвовать в клубе
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereActiveOfEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereAgy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereCreationOfSisto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereFestivalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereHowManyTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereIsHaveInClub($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereMusicStyles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereQuestionForSysto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereTelegram($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereVk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereWhereSysto($value)
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
        'agy',
        'howManyTimes',
        'questionForSysto',
        'telegram',
        'vk',
        'musicStyles',
        'whereSysto',
        'email',
        'user_id',
        'status',
        'is_have_in_club',
        'festival',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
