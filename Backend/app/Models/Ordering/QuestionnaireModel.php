<?php

namespace App\Models\Ordering;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Ordering\QuestionnaireModel
 *
 * @property int $id
 * @property string $order_id
 * @property string $ticket_id
 * @property string|null $email
 * @property bool $is_have_in_club
 * @property string|null $user_id
 * @property string $status
 * @property int $agy Возраст
 * @property int $howManyTimes Сколько раз ты уже бывал на Систо
 * @property string $questionForSysto Ответь кратко и честно на простой вопрос Зачем ты едешь на Систо?
 * @property string|null $telegram Telegram
 * @property string|null $vk Вконтакте
 * @property string|null $whereSysto Откуда ты узнал о Систо
 * @property string|null $musicStyles Стили музыки, которые предпочитаешь в лесу
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereAgy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereHowManyTimes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereMusicStyles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereQuestionForSysto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereTelegram($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|QuestionnaireModel whereVk($value)
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
    ];
}
