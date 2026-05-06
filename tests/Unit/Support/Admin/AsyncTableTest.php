<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Admin;

use App\Support\Admin\AsyncTable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AsyncTableTest extends TestCase
{
    public function test_request_rules_add_common_table_controls(): void
    {
        $rules = AsyncTable::requestRules([
            'search' => ['nullable', 'string', 'max:100'],
        ], ['name', 'created_at']);

        $this->assertSame(['nullable', 'string', 'max:100'], $rules['search']);
        $this->assertSame(['nullable', 'string', 'in:name,created_at'], $rules['sort']);
        $this->assertSame(['nullable', 'string', 'in:asc,desc'], $rules['direction']);
        $this->assertSame(['nullable', 'integer', 'in:5,10,25,50'], $rules['per_page']);
        $this->assertSame(['nullable', 'integer', 'min:1'], $rules['page']);
    }

    public function test_options_resolve_defaults_and_validated_values(): void
    {
        $asyncTableOptions = AsyncTable::options([], 'created_at', 'desc', 25);

        $this->assertSame('created_at', $asyncTableOptions->sort);
        $this->assertSame('desc', $asyncTableOptions->direction);
        $this->assertSame(25, $asyncTableOptions->perPage);

        $options = AsyncTable::options([
            'sort' => 'name',
            'direction' => 'asc',
            'per_page' => '10',
        ], 'created_at', 'desc', 25);

        $this->assertSame('name', $options->sort);
        $this->assertSame('asc', $options->direction);
        $this->assertSame(10, $options->perPage);
    }

    public function test_pagination_metadata_matches_async_table_response_shape(): void
    {
        $lengthAwarePaginator = new LengthAwarePaginator(
            items: collect(['alpha', 'bravo']),
            total: 12,
            perPage: 2,
            currentPage: 3
        );

        $this->assertSame([
            'current_page' => 3,
            'last_page' => 6,
            'from' => 5,
            'to' => 6,
            'total' => 12,
            'per_page' => 2,
        ], AsyncTable::pagination($lengthAwarePaginator));
    }

    public function test_json_renders_rows_view_with_pagination_metadata(): void
    {
        $viewPath = storage_path('framework/testing/async-table-views');

        File::ensureDirectoryExists($viewPath);
        File::put($viewPath . '/rows.blade.php', '@foreach ($items as $item)<span>{{ $item }}</span>@endforeach');
        View::addNamespace('async-table-test', $viewPath);

        $lengthAwarePaginator = new LengthAwarePaginator(
            items: collect(['alpha', 'bravo']),
            total: 7,
            perPage: 2,
            currentPage: 2
        );

        $jsonResponse = AsyncTable::json($lengthAwarePaginator, 'async-table-test::rows', [
            'items' => $lengthAwarePaginator,
        ]);

        $this->assertSame(200, $jsonResponse->getStatusCode());
        $this->assertSame('<span>alpha</span><span>bravo</span>', $jsonResponse->getData(true)['html']);
        $this->assertSame([
            'current_page' => 2,
            'last_page' => 4,
            'from' => 3,
            'to' => 4,
            'total' => 7,
            'per_page' => 2,
        ], $jsonResponse->getData(true)['pagination']);
    }
}
