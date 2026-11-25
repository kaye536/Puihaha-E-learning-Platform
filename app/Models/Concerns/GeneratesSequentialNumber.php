<?php

namespace App\Models\Concerns;

trait GeneratesSequentialNumber
{
    /**
     * Build the next unique number such as PREFIX0001 for the given column.
     * The loop guarantees uniqueness even when numbers were typed manually.
     */
    protected static function nextSequentialNumber(string $column, string $prefix, int $pad): string
    {
        $last = static::query()
            ->where($column, 'like', $prefix.'%')
            ->orderByDesc($column)
            ->value($column);

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        do {
            $candidate = $prefix.str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
            $next++;
        } while (static::query()->where($column, $candidate)->exists());

        return $candidate;
    }
}
