<?php

declare(strict_types=1);

namespace Shared\Domain\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Shared\Domain\Criteria\Filters;

class FilterBuilder
{
    public static function build(Model|Builder $builder, Filters $filters): Model|Builder|null
    {
        foreach ($filters as $filter) {
            if (null !== $filter->value()->value()) {
                $builder = $builder->where(
                    $filter->field()->value(),
                    $filter->operator()->value(),
                    $filter->operator()->value() === 'LIKE' ? '%' . $filter->value()->value() . '%' : $filter->value()->value()
                );
            }
        }

        return $builder;
    }
}