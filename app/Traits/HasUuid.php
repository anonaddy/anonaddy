<?php

namespace App\Traits;

use Ramsey\Uuid\Uuid;

trait HasUuid
{
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (! $model->id) {
                $model->{$model->getKeyName()} = Uuid::uuid4();
            }
        });
    }
}
