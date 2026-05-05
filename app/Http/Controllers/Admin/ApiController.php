<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\User;
use App\Support\Admin\AsyncTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin controller for monitoring API usage and managing rate limits.
 *
 * This controller provides administrative tools to:
 * - List and filter API usage logs (per user)
 * - View and configure dynamic API rate limits
 *
 * Background:
 * Limits are enforced dynamically using a custom throttle middleware.
 * Default fallback limit: 30 requests per minute if not configured.
 *
 * Rate limiting logic ensures:
 * - Tier-based control over API request frequency
 * - HTTP 429 response when limit is exceeded
 * - Informative rate limit headers returned per request
 */
class ApiController extends Controller
{
    /**
     * Display a listing of API logs.
     *
     * This method retrieves and displays a paginated list of API logs, with optional filtering by user.
     *
     * @param  Request  $request  The HTTP request instance, potentially containing a 'user_id' for filtering.
     * @return View|JsonResponse The view displaying the API logs or async table rows.
     */
    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate(AsyncTable::requestRules([
            'search' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'string', 'exists:users,id'],
        ], ['created_at', 'email', 'route']));
        $asyncTableOptions = AsyncTable::options($validated, 'created_at', 'desc', 25);

        $query = ApiLog::query()
            ->withoutGlobalScope('api_logs')
            ->with('user')
            ->select('api_logs.*')
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('api_logs.route', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn (Builder $builder): Builder => $builder->where('email', 'like', '%' . $search . '%'));
                });
            })
            ->when($validated['user_id'] ?? null, fn (Builder $builder, string $userId): Builder => $builder->where('user_id', $userId));

        if ($asyncTableOptions->sort === 'email') {
            $query->join('users as sort_users', 'sort_users.id', '=', 'api_logs.user_id')
                ->orderBy('sort_users.email', $asyncTableOptions->direction)
                ->latest('api_logs.created_at');
        } else {
            $query->orderBy('api_logs.' . $asyncTableOptions->sort, $asyncTableOptions->direction)
                ->orderBy('api_logs.id');
        }

        $lengthAwarePaginator = $query->paginate($asyncTableOptions->perPage);

        if ($request->expectsJson()) {
            return AsyncTable::json($lengthAwarePaginator, 'admin.api.partials.rows', ['apiLogs' => $lengthAwarePaginator]);
        }

        $users = User::query()->select('id', 'email')->orderBy('email')->get();

        return view('admin.api.index', [
            'apiLogs' => $lengthAwarePaginator,
            'users' => $users,
            'filters' => [
                [
                    'name' => 'user_id',
                    'label' => __('user.title'),
                    'placeholder' => __('search.filter.text', ['attribute' => __('user.title')]),
                    'options' => $users->mapWithKeys(fn (User $user): array => [$user->id => $user->email])->all(),
                ],
            ],
            'activeFilters' => [
                'user_id' => (string) ($validated['user_id'] ?? ''),
            ],
            'sort' => $asyncTableOptions->sort,
            'direction' => $asyncTableOptions->direction,
        ]);
    }
}
