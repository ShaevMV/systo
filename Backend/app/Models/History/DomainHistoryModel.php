<?php

declare(strict_types=1);

namespace App\Models\History;

use Illuminate\Database\Eloquent\Model;

final class DomainHistoryModel extends Model
{
    public const TABLE = 'domain_history';

    protected $table = self::TABLE;
    public $timestamps = false;

    protected $fillable = [
        'aggregate_id',
        'aggregate_type',
        'event_name',
        'payload',
        'actor_id',
        'actor_type',
    ];

    protected $casts = [
        'payload'     => 'array',
        'occurred_at' => 'datetime',
    ];
}
