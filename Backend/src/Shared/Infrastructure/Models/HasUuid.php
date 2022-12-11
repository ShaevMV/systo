<?php
declare(strict_types=1);

namespace Tickets\Shared\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Tickets\Shared\Domain\ValueObject\Uuid;

trait HasUuid
{
    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    public static function booted(): void
    {
        static::creating(static function (Model $model) {
            // Set attribute for new model's primary key (ID) to an uuid.
            $model->setAttribute($model->getKeyName(), Uuid::random());
        });
    }
}
