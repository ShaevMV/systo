<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FriendlyTicket
 *
 * @property int $id
 * @property string $email
 * @property string $fio
 * @property string $seller
 * @property string $fio_friendly
 * @property int $count
 * @property float $price
 * @property int $user_id
 * @property string $comment
 * @property string $festival_id
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class FriendlyTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'fio',
        'seller',
        'price',
        'user_id',
        'fio_friendly',
        'comment',
    ];
}
