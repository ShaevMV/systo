<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketsModel extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'kilter',
        'type',
        'uuid',
        'name',
        'email',
        'phone',
        'date_order',
        'status',
        'date_change',
    ];
}
