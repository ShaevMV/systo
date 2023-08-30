<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class FriendlyTicket
 *
 * @property int $id
 * @property string $email
 * @property string $fio
 * @property string $project
 * @property string $curator
 * @property string $type_member
 * @property int $user_id
 * @property string $comment
 * @property string $festival_id
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class ListTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'fio',
        'project',
        'curator',
        'user_id',
        'fio',
        'type_member',
        'comment',
        'festival_id'
    ];
}
