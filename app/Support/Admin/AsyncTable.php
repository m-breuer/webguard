<?php

declare(strict_types=1);

namespace App\Support\Admin;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class AsyncTable
{
    private const DIRECTION_RULE = ['nullable', 'string', 'in:asc,desc'];

    private const PER_PAGE_RULE = ['nullable', 'integer', 'in:5,10,25,50'];

    private const PAGE_RULE = ['nullable', 'integer', 'min:1'];

    /**
     * @param  array<string, mixed>  $rules
     * @param  list<string>  $sortableColumns
     * @return array<string, mixed>
     */
    public static function requestRules(array $rules, array $sortableColumns): array
    {
        return array_merge($rules, [
            'sort' => ['nullable', 'string', 'in:' . implode(',', $sortableColumns)],
            'direction' => self::DIRECTION_RULE,
            'per_page' => self::PER_PAGE_RULE,
            'page' => self::PAGE_RULE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function options(
        array $validated,
        string $defaultSort,
        string $defaultDirection,
        int $defaultPerPage
    ): AsyncTableOptions {
        return new AsyncTableOptions(
            sort: (string) ($validated['sort'] ?? $defaultSort),
            direction: (string) ($validated['direction'] ?? $defaultDirection),
            perPage: (int) ($validated['per_page'] ?? $defaultPerPage),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function json(LengthAwarePaginator $lengthAwarePaginator, string $view, array $data): JsonResponse
    {
        return response()->json([
            'html' => view($view, $data)->render(),
            'pagination' => self::pagination($lengthAwarePaginator),
        ]);
    }

    /**
     * @return array{current_page: int, last_page: int, from: int|null, to: int|null, total: int, per_page: int}
     */
    public static function pagination(LengthAwarePaginator $lengthAwarePaginator): array
    {
        return [
            'current_page' => $lengthAwarePaginator->currentPage(),
            'last_page' => $lengthAwarePaginator->lastPage(),
            'from' => $lengthAwarePaginator->firstItem(),
            'to' => $lengthAwarePaginator->lastItem(),
            'total' => $lengthAwarePaginator->total(),
            'per_page' => $lengthAwarePaginator->perPage(),
        ];
    }
}
