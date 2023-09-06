<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'auto';

    protected $fillable = [
        'curator',
        'project',
        'auto',
        'festival_id',
        'comment',
        'change_id',
        'date_change',
    ];
}
