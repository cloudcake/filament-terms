<?php

namespace Cloudcake\Terms\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasRenamableColumns
{
    public static function renamable(): array
    {
        /** @var Model $model */
        $model = new (static::class);

        return collect(array_keys($model->getCasts()))
            ->filter(fn($column) => !in_array($column, $model->getHidden()))
            ->all();
    }
}