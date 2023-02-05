<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\PasswordResets
 *
 * @property string $email
 * @property string $token
 * @property Carbon|null $created_at
 * @method static Builder|PasswordResets newModelQuery()
 * @method static Builder|PasswordResets newQuery()
 * @method static Builder|PasswordResets query()
 * @method static Builder|PasswordResets whereCreatedAt($value)
 * @method static Builder|PasswordResets whereEmail($value)
 * @method static Builder|PasswordResets whereToken($value)
 * @mixin Eloquent
 */
class PasswordResets extends Model
{
    public const TABLE = 'password_resets';
    protected $table = self::TABLE;
    const UPDATED_AT = null;
    protected $fillable = ['token', 'email', 'created_at'];
}
