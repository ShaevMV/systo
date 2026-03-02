<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models\Festival{
/**
 * App\Models\Festival\FestivalModel
 *
 * @property string $id
 * @property string $year
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string|null $view
 * @method static Builder|FestivalModel newModelQuery()
 * @method static Builder|FestivalModel newQuery()
 * @method static Builder|FestivalModel query()
 * @method static Builder|FestivalModel whereActive($value)
 * @method static Builder|FestivalModel whereCreatedAt($value)
 * @method static Builder|FestivalModel whereId($value)
 * @method static Builder|FestivalModel whereName($value)
 * @method static Builder|FestivalModel whereUpdatedAt($value)
 * @method static Builder|FestivalModel whereView($value)
 * @method static Builder|FestivalModel whereYear($value)
 * @mixin Eloquent
 */
	final class FestivalModel extends \Eloquent {}
}

namespace App\Models\Festival{
/**
 * App\Models\Festival\TicketTypeFestivalModel
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
	final class TicketTypeFestivalModel extends \Eloquent {}
}

namespace App\Models\Festival{
/**
 * App\Models\Festival\TicketTypesModel
 *
 * @property string $id
 * @property string $name
 * @property float $price
 * @property int|null $groupLimit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $sort
 * @property int $active
 * @property int $is_live_ticket
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Festival\FestivalModel[] $festivals
 * @property-read int|null $festivals_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Festival\TicketTypesPriceModel[] $ticketTypePrice
 * @property-read int|null $ticket_type_price_count
 * @method static Builder|TicketTypesModel newModelQuery()
 * @method static Builder|TicketTypesModel newQuery()
 * @method static Builder|TicketTypesModel query()
 * @method static Builder|TicketTypesModel whereActive($value)
 * @method static Builder|TicketTypesModel whereCreatedAt($value)
 * @method static Builder|TicketTypesModel whereGroupLimit($value)
 * @method static Builder|TicketTypesModel whereId($value)
 * @method static Builder|TicketTypesModel whereIsLiveTicket($value)
 * @method static Builder|TicketTypesModel whereName($value)
 * @method static Builder|TicketTypesModel wherePrice($value)
 * @method static Builder|TicketTypesModel whereSort($value)
 * @method static Builder|TicketTypesModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
	class TicketTypesModel extends \Eloquent {}
}

namespace App\Models\Festival{
/**
 * App\Models\Festival\TicketTypesPriceModel
 *
 * @property string $id
 * @property string $ticket_type_id
 * @property float $price
 * @property string $before_date
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TicketTypesPriceModel newModelQuery()
 * @method static Builder|TicketTypesPriceModel newQuery()
 * @method static Builder|TicketTypesPriceModel query()
 * @method static Builder|TicketTypesPriceModel whereBeforeDate($value)
 * @method static Builder|TicketTypesPriceModel whereCreatedAt($value)
 * @method static Builder|TicketTypesPriceModel whereDeletedAt($value)
 * @method static Builder|TicketTypesPriceModel whereId($value)
 * @method static Builder|TicketTypesPriceModel wherePrice($value)
 * @method static Builder|TicketTypesPriceModel whereTicketTypeId($value)
 * @method static Builder|TicketTypesPriceModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
	class TicketTypesPriceModel extends \Eloquent {}
}

namespace App\Models\Festival{
/**
 * App\Models\Festival\TypesOfPaymentModel
 *
 * @property string $id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $active
 * @property int $sort
 * @property string $card
 * @property int $is_billing
 * @method static Builder|TypesOfPaymentModel newModelQuery()
 * @method static Builder|TypesOfPaymentModel newQuery()
 * @method static Builder|TypesOfPaymentModel query()
 * @method static Builder|TypesOfPaymentModel whereActive($value)
 * @method static Builder|TypesOfPaymentModel whereCard($value)
 * @method static Builder|TypesOfPaymentModel whereCreatedAt($value)
 * @method static Builder|TypesOfPaymentModel whereId($value)
 * @method static Builder|TypesOfPaymentModel whereIsBilling($value)
 * @method static Builder|TypesOfPaymentModel whereName($value)
 * @method static Builder|TypesOfPaymentModel whereSort($value)
 * @method static Builder|TypesOfPaymentModel whereUpdatedAt($value)
 * @mixin Eloquent
 * @property string|null $user_external_id Связь с продавцом или реализатором
 * @method static Builder|TypesOfPaymentModel whereUserExternalId($value)
 * @property string|null $ticket_type_id Связь с типом билета
 * @method static Builder|TypesOfPaymentModel whereTicketTypeId($value)
 * @method static Builder|TypesOfPaymentModel whereEmail($value)
 */
	class TypesOfPaymentModel extends \Eloquent {}
}

namespace App\Models\Invite{
/**
 * App\Models\Invite\InviteModel
 *
 * @property string $id
 * @property mixed $order_id_list
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|InviteModel newModelQuery()
 * @method static Builder|InviteModel newQuery()
 * @method static Builder|InviteModel query()
 * @method static Builder|InviteModel whereCreatedAt($value)
 * @method static Builder|InviteModel whereId($value)
 * @method static Builder|InviteModel whereOrderIdList($value)
 * @method static Builder|InviteModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
	final class InviteModel extends \Eloquent {}
}

namespace App\Models\Ordering{
/**
 * App\Models\Ordering\CommentOrderTicketModel
 *
 * @property string $id
 * @property string $user_id
 * @property string $order_tickets_id
 * @property string $comment
 * @property int $is_checkin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Ordering\OrderTicketModel|null $order
 * @method static Builder|CommentOrderTicketModel newModelQuery()
 * @method static Builder|CommentOrderTicketModel newQuery()
 * @method static Builder|CommentOrderTicketModel query()
 * @method static Builder|CommentOrderTicketModel whereComment($value)
 * @method static Builder|CommentOrderTicketModel whereCreatedAt($value)
 * @method static Builder|CommentOrderTicketModel whereId($value)
 * @method static Builder|CommentOrderTicketModel whereIsCheckin($value)
 * @method static Builder|CommentOrderTicketModel whereOrderTicketsId($value)
 * @method static Builder|CommentOrderTicketModel whereUpdatedAt($value)
 * @method static Builder|CommentOrderTicketModel whereUserId($value)
 * @mixin Eloquent
 */
	final class CommentOrderTicketModel extends \Eloquent {}
}

namespace App\Models\Ordering\InfoForOrder{
/**
 * App\Models\Festival\TicketTypesModel
 *
 * @property string $id
 * @property string $name
 * @property float $price
 * @property int|null $groupLimit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $sort
 * @property int $active
 * @property int $is_live_ticket
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Festival\FestivalModel[] $festivals
 * @property-read int|null $festivals_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Festival\TicketTypesPriceModel[] $ticketTypePrice
 * @property-read int|null $ticket_type_price_count
 * @method static Builder|TicketTypesModel newModelQuery()
 * @method static Builder|TicketTypesModel newQuery()
 * @method static Builder|TicketTypesModel query()
 * @method static Builder|TicketTypesModel whereActive($value)
 * @method static Builder|TicketTypesModel whereCreatedAt($value)
 * @method static Builder|TicketTypesModel whereGroupLimit($value)
 * @method static Builder|TicketTypesModel whereId($value)
 * @method static Builder|TicketTypesModel whereIsLiveTicket($value)
 * @method static Builder|TicketTypesModel whereName($value)
 * @method static Builder|TicketTypesModel wherePrice($value)
 * @method static Builder|TicketTypesModel whereSort($value)
 * @method static Builder|TicketTypesModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
	class TicketTypesModel extends \Eloquent {}
}

namespace App\Models\Ordering\InfoForOrder{
/**
 * App\Models\Festival\TypesOfPaymentModel
 *
 * @property string $id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $active
 * @property int $sort
 * @property string $card
 * @property int $is_billing
 * @method static Builder|TypesOfPaymentModel newModelQuery()
 * @method static Builder|TypesOfPaymentModel newQuery()
 * @method static Builder|TypesOfPaymentModel query()
 * @method static Builder|TypesOfPaymentModel whereActive($value)
 * @method static Builder|TypesOfPaymentModel whereCard($value)
 * @method static Builder|TypesOfPaymentModel whereCreatedAt($value)
 * @method static Builder|TypesOfPaymentModel whereId($value)
 * @method static Builder|TypesOfPaymentModel whereIsBilling($value)
 * @method static Builder|TypesOfPaymentModel whereName($value)
 * @method static Builder|TypesOfPaymentModel whereSort($value)
 * @method static Builder|TypesOfPaymentModel whereUpdatedAt($value)
 * @mixin Eloquent
 * @property string|null $user_external_id Связь с продавцом или реализатором
 * @method static Builder|TypesOfPaymentModel whereUserExternalId($value)
 * @property string|null $ticket_type_id Связь с типом билета
 * @method static Builder|TypesOfPaymentModel whereTicketTypeId($value)
 * @method static Builder|TypesOfPaymentModel whereEmail($value)
 */
	class TypesOfPaymentModel extends \Eloquent {}
}

namespace App\Models\Ordering{
/**
 * App\Models\Ordering\OrderTicketModel
 *
 * @property int $kilter
 * @property string $id
 * @property mixed $guests
 * @property string $festival_id
 * @property string $user_id
 * @property string $ticket_type_id
 * @property string|null $promo_code
 * @property string $id_buy
 * @property string $phone
 * @property string $types_of_payment_id
 * @property float $price
 * @property float $discount
 * @property string $status
 * @property string $date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|\App\Models\Ordering\CommentOrderTicketModel[] $comments
 * @property-read int|null $comments_count
 * @property-read TicketTypesModel|null $ticketType
 * @property-read Collection|TicketModel[] $tickets
 * @property-read int|null $tickets_count
 * @property-read TypesOfPaymentModel|null $typeOfPayment
 * @property-read User|null $users
 * @method static Builder|OrderTicketModel newModelQuery()
 * @method static Builder|OrderTicketModel newQuery()
 * @method static Builder|OrderTicketModel query()
 * @method static Builder|OrderTicketModel whereCreatedAt($value)
 * @method static Builder|OrderTicketModel whereDate($value)
 * @method static Builder|OrderTicketModel whereDiscount($value)
 * @method static Builder|OrderTicketModel whereFestivalId($value)
 * @method static Builder|OrderTicketModel whereGuests($value)
 * @method static Builder|OrderTicketModel whereId($value)
 * @method static Builder|OrderTicketModel whereIdBuy($value)
 * @method static Builder|OrderTicketModel whereKilter($value)
 * @method static Builder|OrderTicketModel wherePhone($value)
 * @method static Builder|OrderTicketModel wherePrice($value)
 * @method static Builder|OrderTicketModel wherePromoCode($value)
 * @method static Builder|OrderTicketModel whereStatus($value)
 * @method static Builder|OrderTicketModel whereTicketTypeId($value)
 * @method static Builder|OrderTicketModel whereTypesOfPaymentId($value)
 * @method static Builder|OrderTicketModel whereUpdatedAt($value)
 * @method static Builder|OrderTicketModel whereUserId($value)
 * @mixin Eloquent
 * @property string $type
 * @method static Builder|OrderTicketModel whereType($value)
 * @property string|null $friendly_id
 * @method static Builder|OrderTicketModel whereFriendlyId($value)
 */
	final class OrderTicketModel extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User\PasswordResets
 *
 * @property string $email
 * @property string $token
 * @property Carbon|null $created_at
 * @method static Builder|PasswordResets newModelQuery()
 * @method static Builder|PasswordResets newQuery()
 * @method static Builder|PasswordResets query()
 * @method static Builder|PasswordResets whereCreatedAt($value)
 * @method static Builder|PasswordResets whereEmail($value)
 * @method static Builder|PasswordResets whereToken($value)
 * @mixin Eloquent
 */
	class PasswordResets extends \Eloquent {}
}

namespace App\Models\PromoCode{
/**
 * App\Models\PromoCode\ExternalPromoCodeModel
 *
 * @property string $id
 * @property string|null $order_tickets_id
 * @property string $promocode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|ExternalPromoCodeModel newModelQuery()
 * @method static Builder|ExternalPromoCodeModel newQuery()
 * @method static Builder|ExternalPromoCodeModel query()
 * @method static Builder|ExternalPromoCodeModel whereCreatedAt($value)
 * @method static Builder|ExternalPromoCodeModel whereId($value)
 * @method static Builder|ExternalPromoCodeModel whereOrderTicketsId($value)
 * @method static Builder|ExternalPromoCodeModel wherePromocode($value)
 * @method static Builder|ExternalPromoCodeModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
	class ExternalPromoCodeModel extends \Eloquent {}
}

namespace App\Models\PromoCode{
/**
 * App\Models\PromoCode\PromoCodeModel
 *
 * @property string $id
 * @property string $name
 * @property float $discount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $is_percent
 * @property int $active
 * @property int|null $limit
 * @property string|null $ticket_type_id
 * @property string|null $festival_id
 * @property-read \Illuminate\Database\Eloquent\Collection|OrderTicketModel[] $orderTickets
 * @property-read int|null $order_tickets_count
 * @method static Builder|PromoCodeModel newModelQuery()
 * @method static Builder|PromoCodeModel newQuery()
 * @method static Builder|PromoCodeModel query()
 * @method static Builder|PromoCodeModel whereActive($value)
 * @method static Builder|PromoCodeModel whereCreatedAt($value)
 * @method static Builder|PromoCodeModel whereDiscount($value)
 * @method static Builder|PromoCodeModel whereFestivalId($value)
 * @method static Builder|PromoCodeModel whereId($value)
 * @method static Builder|PromoCodeModel whereIsPercent($value)
 * @method static Builder|PromoCodeModel whereLimit($value)
 * @method static Builder|PromoCodeModel whereName($value)
 * @method static Builder|PromoCodeModel whereTicketTypeId($value)
 * @method static Builder|PromoCodeModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
	class PromoCodeModel extends \Eloquent {}
}

namespace App\Models\Questionnaire{
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
	class QuestionnaireModel extends \Eloquent {}
}

namespace App\Models\Tickets{
/**
 * App\Models\Tickets\TicketModel
 *
 * @property int $kilter
 * @property string $id
 * @property string $order_ticket_id
 * @property string $name
 * @property string $status
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $festival_id
 * @property-read OrderTicketModel|null $orderTicket
 * @method static Builder|TicketModel newModelQuery()
 * @method static Builder|TicketModel newQuery()
 * @method static \Illuminate\Database\Query\Builder|TicketModel onlyTrashed()
 * @method static Builder|TicketModel query()
 * @method static Builder|TicketModel whereCreatedAt($value)
 * @method static Builder|TicketModel whereDeletedAt($value)
 * @method static Builder|TicketModel whereFestivalId($value)
 * @method static Builder|TicketModel whereId($value)
 * @method static Builder|TicketModel whereKilter($value)
 * @method static Builder|TicketModel whereName($value)
 * @method static Builder|TicketModel whereOrderTicketId($value)
 * @method static Builder|TicketModel whereStatus($value)
 * @method static Builder|TicketModel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|TicketModel withTrashed()
 * @method static \Illuminate\Database\Query\Builder|TicketModel withoutTrashed()
 * @mixin Eloquent
 * @property string|null $email
 * @method static Builder|TicketModel whereEmail($value)
 */
	class TicketModel extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User\User
 *
 * @property string $id
 * @property string|null $name
 * @property string|null $phone
 * @property string|null $city
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property int $is_admin
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $is_manager
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereCity($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereEmailVerifiedAt($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereIsAdmin($value)
 * @method static Builder|User whereIsManager($value)
 * @method static Builder|User whereName($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User wherePhone($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @mixin Eloquent
 * @property string $role Роль пользователя в системе
 *                 Гость/менеджер/продавец/пушер(френдли реализатор)/админ
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static Builder|User whereRole($value)
 */
	class User extends \Eloquent implements \PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject {}
}

