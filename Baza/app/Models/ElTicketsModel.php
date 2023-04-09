<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElTicketsModel extends Model
{
    protected $table = 'elTickets';

    protected $fillable = [
        'kilter',
        'uuid',
        'name',
        'email',
        'phone',
        'date_order',
    ];
}
