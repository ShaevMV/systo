<?php

namespace App\Models;

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
