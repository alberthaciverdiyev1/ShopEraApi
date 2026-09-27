<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

if (!function_exists('rangeFilter')) {
    /**
     * Apply an exact, between, or min/max filter on a query.
     *
     * @param Builder $query
     * @param string $column
     * @param array $params
     * @return Builder
     */
    function rangeFilter(Builder $query, string $column, array $params): Builder
    {
        $exact = $params[$column] ?? null;
        $min = $params[$column . '_min'] ?? null;
        $max = $params[$column . '_max'] ?? null;

        if (!empty($exact) && empty($min) && empty($max)) {
            $query->where($column, $exact);
        } else if (!empty($min) && !empty($max)) {
            $query->whereBetween($column, [$min, $max]);
        } else {
            if (!empty($min)) {
                $query->where($column, '>=', $min);
            }
            if (!empty($max)) {
                $query->where($column, '<=', $max);
            }
        }

        return $query;
    }


}
if (!function_exists('rangeDateFilter')) {
    /**
     * @param Builder $query
     * @param string $column
     * @param array $params
     * @return Builder
     */
    function rangeDateFilter(Builder $query, string $column, array $params): Builder
    {
        $min = $params[$column . '_min'] ?? null;
        $max = $params[$column . '_max'] ?? null;

        if ($min && !$max) {
            $max = now();
        } elseif (!$min && $max) {
            $min = '1970-01-01';
        } elseif (!$min && !$max) {
            return $query;
        }

        return $query->whereBetween($column, [$min, $max]);
    }
}
if (!function_exists('orderBy')) {
    function orderBy(Builder $query, array $params): Builder
    {
        if (!empty($params['order_by']) && !empty($params['order_type'])) {
            $query->orderBy($params['order_by'], $params['order_type']);
        } else if (!empty($params['order_by']) && empty($params['order_type'])) {
            $query->orderBy($params['order_by'], 'desc');
        } else if (empty($params['order_by']) && !empty($params['order_type'])) {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}


//if (!function_exists('filterLike')) {
//
//    function filterLike(Builder $query, array|string $columns, array $params): Builder
//    {
//        if (!is_array($columns)) {
//            $columns = [$columns];
//        }
//
//        if (empty($params['search'])) {
//            return $query;
//        }
//
//        $search = $params['search'];
//        $locales = config('app.available_locales', ['az', 'en', 'ru', 'tr']);
//        $model = $query->getModel();
//        $tableName = $model->getTable();
//
//        $query->where(function ($q) use ($columns, $search, $locales, $model, $tableName) {
//            foreach ($columns as $column) {
//                $isTranslatable = method_exists($model, 'isTranslatableAttribute')
//                    && $model->isTranslatableAttribute($column);
//
//                if ($isTranslatable) {
//                    $q->orWhere(function ($q2) use ($column, $search, $locales) {
//                        foreach ($locales as $locale) {
//                            $q2->orWhere("{$column}->{$locale}", 'ilike', "%{$search}%");
//                        }
//                    });
//                } else {
//                    $q->orWhere("{$tableName}.{$column}", 'ilike', "%{$search}%");
//                }
//            }
//        });
//
//        return $query;
//    }
//}


//if (!function_exists('filterLike')) {
//
//    function filterLike(Builder $query, array|string $columns, array $params): Builder
//    {
//        if (!is_array($columns)) {
//            $columns = [$columns];
//        }
//
//        if (empty($params['search'])) {
//            return $query;
//        }
//
//        $search = trim($params['search']);
//        $appLocale = app()->getLocale();
//        $locales = config('app.available_locales', ['az', 'en', 'ru', 'tr']);
//        $model = $query->getModel();
//        $tableName = $model->getTable();
//
//        DB::statement("SET pg_trgm.similarity_threshold = 0.15");
//
//        $query->where(function ($q) use ($columns, $search, $locales, $model, $tableName) {
//            foreach ($columns as $column) {
//                $isTranslatable = method_exists($model, 'isTranslatableAttribute')
//                    && $model->isTranslatableAttribute($column);
//
//                if ($isTranslatable) {
//                    $q->orWhere(function ($q2) use ($column, $search, $locales) {
//                        foreach ($locales as $locale) {
//                            $q2->orWhereRaw("({$column}->>'{$locale}') ilike ?", ["%{$search}%"])
//                                ->orWhereRaw("({$column}->>'{$locale}') % ?", [$search]);
//                        }
//                    });
//                } else {
//                    $q->orWhereRaw("{$tableName}.{$column}::text ilike ?", ["%{$search}%"])
//                        ->orWhereRaw("{$tableName}.{$column}::text % ?", [$search]);
//                }
//            }
//        });
//
//        $firstCol = $columns[0];
//        $isFirstTranslatable = method_exists($model, 'isTranslatableAttribute')
//            && $model->isTranslatableAttribute($firstCol);
//
//        if ($isFirstTranslatable) {
//            $query->orderByRaw("similarity({$firstCol}->>'{$appLocale}', ?) DESC", [$search]);
//        } else {
//            $query->orderByRaw("similarity({$tableName}.{$firstCol}::text, ?) DESC", [$search]);
//        }
//
//        return $query;
//    }
//}


function filterLike(Builder $query, array|string $columns, array $params): Builder
{
    $search = trim($params['search'] ?? '');
    if (empty($search) || mb_strlen($search) < 2) return $query;

    $columns = (array) $columns;
    $model = $query->getModel();
    $tableName = $model->getTable();
    $locale = app()->getLocale();

    $query->where(function ($q) use ($columns, $search, $model, $tableName, $locale) {
        foreach ($columns as $column) {
            $isTranslatable = method_exists($model, 'isTranslatableAttribute')
                && $model->isTranslatableAttribute($column);

            if ($isTranslatable) {
                $columnPath = "({$column}->>'{$locale}')";

                $q->orWhereRaw("unaccent({$columnPath}) ILIKE unaccent(?)", ["%{$search}%"])
                    ->orWhereRaw("{$columnPath} % ?", [$search]);
            } else {
                $q->orWhereRaw("unaccent({$tableName}.{$column}::text) ILIKE unaccent(?)", ["%{$search}%"]);
            }
        }
    });

    $firstCol = $columns[0];
    if (method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($firstCol)) {
        $query->orderByRaw("similarity(unaccent({$firstCol}->>'{$locale}'), unaccent(?)) DESC", [$search]);
    }

    return $query;
}

if (!function_exists('whereEach')) {
    function whereEach(Builder $query, array|string $columns, array $params): Builder
    {
        if (is_array($columns)) {
            foreach ($columns as $column) {
                if (!empty($params[$column])) {
                    $query->where($column, '=', $params[$column]);
                }
            }
        } elseif (is_string($columns) && !empty($params[$columns])) {
            $query->where($columns, '=', $params[$columns]);
        }

        return $query;
    }
}

