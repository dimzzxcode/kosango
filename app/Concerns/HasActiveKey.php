<?php

namespace App\Concerns;

/**
 * Keeps the generated-style active_key column in sync so unique indexes only
 * apply to rows that are not soft deleted.
 */
trait HasActiveKey
{
    public static function bootHasActiveKey(): void
    {
        static::creating(function (self $model): void {
            $model->active_key = 1;
        });

        static::deleting(function (self $model): void {
            if (! $model->isForceDeleting()) {
                $model->newQuery()
                    ->whereKey($model->getKey())
                    ->update(['active_key' => null]);
            }
        });

        static::restoring(function (self $model): void {
            $model->newQuery()
                ->whereKey($model->getKey())
                ->update(['active_key' => 1]);
        });
    }
}
