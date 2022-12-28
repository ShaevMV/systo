<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models\Ordering{
/**
 * App\Models\Tickets\Ordering\CommentOrderTicket
 *
 * @property string $id
 * @property string $user_id
 * @property string $order_tickets_id
 * @property string $comment
 * @property int $is_checkin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
 * @property-read OrderTicketModel|null $order
 */
	final class CommentOrderTicketModel extends \Eloquent {}
}

namespace App\Models\Ordering\InfoForOrder{
/**
 * App\Models\Tickets\Ordering\InfoForOrder\Models\PromoCod
 *
 * @property string $id
 * @property string $name
 * @property float $discount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|PromoCodeModel newModelQuery()
 * @method static Builder|PromoCodeModel newQuery()
 * @method static Builder|PromoCodeModel query()
 * @method static Builder|PromoCodeModel whereCreatedAt($value)
 * @method static Builder|PromoCodeModel whereDiscount($value)
 * @method static Builder|PromoCodeModel whereId($value)
 * @method static Builder|PromoCodeModel whereName($value)
 * @method static Builder|PromoCodeModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
	class PromoCodeModel extends \Eloquent {}
}

namespace App\Models\Ordering\InfoForOrder{
/**
 * App\Models\Tickets\Ordering\InfoForOrder\Models\TicketTypes
 *
 * @property string $id
 * @property string $name
 * @property float $price
 * @property int|null $groupLimit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TicketTypesModel newModelQuery()
 * @method static Builder|TicketTypesModel newQuery()
 * @method static Builder|TicketTypesModel query()
 * @method static Builder|TicketTypesModel whereCreatedAt($value)
 * @method static Builder|TicketTypesModel whereGroupLimit($value)
 * @method static Builder|TicketTypesModel whereId($value)
 * @method static Builder|TicketTypesModel whereName($value)
 * @method static Builder|TicketTypesModel wherePrice($value)
 * @method static Builder|TicketTypesModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
	class TicketTypesModel extends \Eloquent {}
}

namespace App\Models\Ordering\InfoForOrder{
/**
 * App\Models\Tickets\Ordering\InfoForOrder\Models\TypesOfPayment
 *
 * @property string $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TypesOfPaymentModel newModelQuery()
 * @method static Builder|TypesOfPaymentModel newQuery()
 * @method static Builder|TypesOfPaymentModel query()
 * @method static Builder|TypesOfPaymentModel whereCreatedAt($value)
 * @method static Builder|TypesOfPaymentModel whereId($value)
 * @method static Builder|TypesOfPaymentModel whereName($value)
 * @method static Builder|TypesOfPaymentModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
	class TypesOfPaymentModel extends \Eloquent {}
}

namespace App\Models\Ordering{
/**
 * App\Models\Tickets\Ordering\OrderTicket
 *
 * @property string $id
 * @property mixed $guests
 * @property string $user_id
 * @property string $ticket_type_id
 * @property string $promo_code
 * @property string $types_of_payment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|OrderTicketModel newModelQuery()
 * @method static Builder|OrderTicketModel newQuery()
 * @method static Builder|OrderTicketModel query()
 * @method static Builder|OrderTicketModel whereCreatedAt($value)
 * @method static Builder|OrderTicketModel whereGuests($value)
 * @method static Builder|OrderTicketModel whereId($value)
 * @method static Builder|OrderTicketModel wherePromoCodeId($value)
 * @method static Builder|OrderTicketModel whereTicketTypeId($value)
 * @method static Builder|OrderTicketModel whereTypesOfPaymentId($value)
 * @method static Builder|OrderTicketModel whereUpdatedAt($value)
 * @method static Builder|OrderTicketModel whereUserId($value)
 * @method static create(array $toArray)
 * @property float $price
 * @property float $discount
 * @property string $status
 * @property string $date
 * @method static Builder|OrderTicketModel whereDate($value)
 * @method static Builder|OrderTicketModel whereDiscount($value)
 * @method static Builder|OrderTicketModel wherePrice($value)
 * @method static Builder|OrderTicketModel wherePromoCode($value)
 * @method static Builder|OrderTicketModel whereStatus($value)
 * @mixin Eloquent
 * @property-read Collection|CommentOrderTicketModel[] $comments
 * @property-read int|null $comments_count
 * @property-read TicketTypesModel $ticketType
 * @property-read TypesOfPaymentModel $typeOfPayment
 */
	final class OrderTicketModel extends \Eloquent {}
}


namespace App\Models{
/**
 * App\Models\User
 *
 * @property string $id
 * @property string|null $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static UserFactory factory(...$parameters)
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereEmailVerifiedAt($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereName($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static Builder|User create(array $toArray)
 * @mixin Eloquent
 * @property int $is_admin
 * @method static Builder|User whereIsAdmin($value)
 */
	class User extends \Eloquent implements \PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject {}
}

