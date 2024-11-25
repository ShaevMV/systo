<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * Class LiveTicket
 *
 * @property int $id
 * @property string $email
 * @property string $fio
 * @property string $seller
 * @property string $kilter
 * @property string $fio_friendly
 * @property string $phone
 * @property int $count
 * @property float $price
 * @property int $user_id
 * @property string $comment
 * @property string $festival_id
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class LiveTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'fio',
        'seller',
        'kilter',
        'price',
        'user_id',
        'fio_friendly',
        'festival_id',
        'comment',
        'phone'
    ];
}
