<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\User;
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
     * @return View The view displaying the API logs.
     */
    public function index(Request $request)
    {
        $lengthAwarePaginator = ApiLog::with('user')
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->user_id))->latest()
            ->paginate(25);

        $users = User::query()->select('id', 'email')->orderBy('email')->get();

        return view('admin.api.index', ['apiLogs' => $lengthAwarePaginator, 'users' => $users]);
    }
}
