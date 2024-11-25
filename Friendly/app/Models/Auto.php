<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class FriendlyTicket
 *
 * @property int $id
 * @property string $project
 * @property string $curator
 * @property string $auto
 * @property int $user_id
 * @property string $comment
 * @property string $festival_id
 * @property Carbon $created_at
 *
 * @package App\Models
 */
class Auto extends Model
{
    use HasFactory;

    protected $table = 'auto';

    protected $fillable = [
        'project',
        'curator',
        'user_id',
        'auto',
        'comment',
        'festival_id'
    ];
}
