<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal\Ui;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Jobs\DeleteUser;
use App\Models\ApiLog;
use App\Models\Monitoring;
use App\Models\Package;
use App\Models\ServerInstance;
use App\Models\User;
use App\Services\UserDeletionPreparationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class AdminWorkspaceController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => [
            'users' => User::query()->count(),
            'packages' => Package::query()->withoutGlobalScope('selectable')->count(),
            'server_instances' => ServerInstance::query()->count(),
            'active_server_instances' => ServerInstance::query()->where('is_active', true)->count(),
            'api_requests_last_24_hours' => ApiLog::query()->withoutGlobalScope('api_logs')->where('created_at', '>=', Date::now()->subDay())->count(),
            'audit_events_last_24_hours' => Activity::query()->where('created_at', '>=', Date::now()->subDay())->count(),
        ]]);
    }

    public function users(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'sort' => ['nullable', Rule::in(['name', 'email', 'role', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $lengthAwarePaginator = User::query()->with('package')
            ->when($validated['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(fn (Builder $builder): Builder => $builder->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($validated['role'] ?? null, fn (Builder $builder, string $role): Builder => $builder->where('role', $role))
            ->orderBy($sort, $direction)
            ->orderByDesc('created_at')
            ->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json(['data' => $this->paginated($lengthAwarePaginator, fn (User $user): array => $this->user($user)), 'options' => [
            'packages' => Package::query()->withoutGlobalScope('selectable')->orderBy('monitoring_limit')->get()->map(fn (Package $package): array => $this->package($package))->all(),
            'roles' => UserRole::values(),
        ]]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'package_id' => ['nullable', 'exists:packages,id'],
        ]);
        $model = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'package_id' => $validated['package_id'] ?? null,
        ]);

        return response()->json(['data' => $this->user($model->load('package'))], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'package_id' => ['nullable', 'exists:packages,id'],
        ]);
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'package_id' => $validated['package_id'] ?? null,
        ]);
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return response()->json(['data' => $this->user($user->load('package'))]);
    }

    public function verifyUser(User $user): JsonResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json(['data' => $this->user($user->load('package'))]);
    }

    public function destroyUser(Request $request, User $user, UserDeletionPreparationService $userDeletionPreparationService): JsonResponse
    {
        if ($request->user()?->is($user)) {
            throw ValidationException::withMessages(['user' => ['You cannot delete your own account.']]);
        }
        activity('user')->performedOn($user)->event('delete_requested')->withProperties(['action' => 'admin_user_deletion_requested'])->log('user_delete_requested');
        $userDeletionPreparationService->disableLoginUntilDeletion($user);
        dispatch(new DeleteUser($user));

        return response()->json(status: 204);
    }

    public function packages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'selectable' => ['nullable', Rule::in(['yes', 'no'])],
            'sort' => ['nullable', Rule::in(['monitoring_limit', 'price', 'is_selectable', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $sort = $validated['sort'] ?? 'price';
        $direction = $validated['direction'] ?? 'asc';
        $lengthAwarePaginator = Package::query()->withoutGlobalScope('selectable')
            ->when($validated['search'] ?? null, fn (Builder $builder, string $search): Builder => $builder->where('monitoring_limit', 'like', "%{$search}%")->orWhere('price', 'like', "%{$search}%"))
            ->when($validated['selectable'] ?? null, fn (Builder $builder, string $selectable): Builder => $builder->where('is_selectable', $selectable === 'yes'))
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json(['data' => $this->paginated($lengthAwarePaginator, fn (Package $package): array => $this->package($package))]);
    }

    public function storePackage(Request $request): JsonResponse
    {
        $validated = $request->validate(['monitoring_limit' => ['required', 'integer', 'min:1'], 'price' => ['required', 'numeric', 'min:0'], 'is_selectable' => ['boolean']]);
        $package = Package::query()->create($validated);

        return response()->json(['data' => $this->package($package)], 201);
    }

    public function updatePackage(Request $request, Package $package): JsonResponse
    {
        $validated = $request->validate(['monitoring_limit' => ['required', 'integer', 'min:1'], 'price' => ['required', 'numeric', 'min:0'], 'is_selectable' => ['boolean']]);
        $package->update($validated);

        return response()->json(['data' => $this->package($package)]);
    }

    public function destroyPackage(Package $package): JsonResponse
    {
        if ($package->users()->exists()) {
            throw ValidationException::withMessages(['package' => ['Packages assigned to users cannot be deleted.']]);
        }
        $package->delete();

        return response()->json(status: 204);
    }

    public function serverInstances(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', Rule::in(['yes', 'no'])],
            'sort' => ['nullable', Rule::in(['display_name', 'code', 'country_code', 'ip_address', 'is_active', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $sort = $validated['sort'] ?? 'code';
        $direction = $validated['direction'] ?? 'asc';
        $lengthAwarePaginator = ServerInstance::query()
            ->when($validated['search'] ?? null, fn (Builder $builder, string $search): Builder => $builder->where('code', 'like', "%{$search}%")->orWhere('display_name', 'like', "%{$search}%")->orWhere('ip_address', 'like', "%{$search}%"))
            ->when($validated['active'] ?? null, fn (Builder $builder, string $active): Builder => $builder->where('is_active', $active === 'yes'))
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json(['data' => $this->paginated($lengthAwarePaginator, fn (ServerInstance $serverInstance): array => $this->serverInstance($serverInstance))]);
    }

    public function storeServerInstance(Request $request): JsonResponse
    {
        $validated = $this->validateServerInstance($request);
        $validated['api_key_hash'] = $validated['api_key'];
        unset($validated['api_key']);
        $serverInstance = ServerInstance::query()->create($validated);

        return response()->json(['data' => $this->serverInstance($serverInstance)], 201);
    }

    public function updateServerInstance(Request $request, ServerInstance $serverInstance): JsonResponse
    {
        $validated = $this->validateServerInstance($request, $serverInstance);
        if (filled($validated['api_key'] ?? null)) {
            $validated['api_key_hash'] = $validated['api_key'];
        }
        unset($validated['api_key']);
        $serverInstance->update($validated);

        return response()->json(['data' => $this->serverInstance($serverInstance)]);
    }

    public function destroyServerInstance(ServerInstance $serverInstance): JsonResponse
    {
        if (Monitoring::query()->withoutGlobalScope('user')->assignedToLocation($serverInstance->code)->exists()) {
            throw ValidationException::withMessages(['server_instance' => ['Server instances with assigned monitorings cannot be deleted.']]);
        }
        $serverInstance->delete();

        return response()->json(status: 204);
    }

    public function apiLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'search' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'string', 'exists:users,id'],
            'sort' => ['nullable', Rule::in(['created_at', 'user_email', 'route'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $lengthAwarePaginator = ApiLog::query()->withoutGlobalScope('api_logs')->with('user')
            ->leftJoin('users as api_log_users', 'api_log_users.id', '=', 'api_logs.user_id')
            ->select('api_logs.*')
            ->when($validated['search'] ?? null, fn (Builder $builder, string $search): Builder => $builder->where(fn (Builder $query): Builder => $query->where('api_logs.route', 'like', "%{$search}%")->orWhere('api_log_users.email', 'like', "%{$search}%")))
            ->when($validated['user_id'] ?? null, fn (Builder $builder, string $userId): Builder => $builder->where('api_logs.user_id', $userId))
            ->orderBy($sort === 'user_email' ? 'api_log_users.email' : "api_logs.{$sort}", $direction)
            ->orderByDesc('api_logs.created_at')
            ->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json([
            'data' => $this->paginated($lengthAwarePaginator, fn (ApiLog $apiLog): array => ['id' => $apiLog->id, 'route' => $apiLog->route, 'created_at' => $apiLog->created_at?->toIso8601String(), 'user_email' => $apiLog->user?->email]),
            'options' => ['users' => User::query()->orderBy('email')->get(['id', 'email'])->map(fn (User $user): array => ['id' => $user->id, 'email' => $user->email])->all()],
        ]);
    }

    public function activityLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'search' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['description', 'event', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $lengthAwarePaginator = Activity::query()->with('causer')
            ->when($validated['search'] ?? null, fn (Builder $builder, string $search): Builder => $builder->where('description', 'like', "%{$search}%")->orWhere('log_name', 'like', "%{$search}%")->orWhere('event', 'like', "%{$search}%"))
            ->when($validated['event'] ?? null, fn (Builder $builder, string $event): Builder => $builder->where('event', $event))
            ->orderBy($sort, $direction)
            ->orderByDesc('created_at')
            ->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json([
            'data' => $this->paginated($lengthAwarePaginator, fn (Activity $activity): array => $this->activityLog($activity)),
            'options' => ['events' => Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event')->values()->all()],
        ]);
    }

    /** @return array<string, mixed> */
    private function user(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role->value, 'package_id' => $user->package_id, 'package_limit' => $user->package?->monitoring_limit, 'email_verified_at' => $user->email_verified_at?->toIso8601String(), 'created_at' => $user->created_at?->toIso8601String()];
    }

    /** @return array<string, mixed> */
    private function package(Package $package): array
    {
        return ['id' => $package->id, 'monitoring_limit' => $package->monitoring_limit, 'price' => (float) $package->price, 'is_selectable' => $package->is_selectable, 'created_at' => $package->created_at?->toIso8601String()];
    }

    /** @return array<string, mixed> */
    private function serverInstance(ServerInstance $serverInstance): array
    {
        return ['id' => $serverInstance->id, 'code' => $serverInstance->code, 'display_name' => $serverInstance->display_name, 'country_code' => $serverInstance->country_code, 'region' => $serverInstance->region, 'ip_address' => $serverInstance->ip_address, 'is_active' => $serverInstance->is_active, 'health' => $serverInstance->healthStatus(), 'last_seen_at' => $serverInstance->last_seen_at?->toIso8601String()];
    }

    /** @return array<string, mixed> */
    private function activityLog(Activity $activity): array
    {
        $attributeChanges = collect($activity->attribute_changes)->all();
        $properties = collect($activity->properties)->all();

        return [
            'id' => (string) $activity->id,
            'description' => $activity->description,
            'log_name' => $activity->log_name,
            'event' => $activity->event,
            'subject_id' => $activity->subject_id,
            'actor' => $activity->causer instanceof User ? $activity->causer->email : null,
            'changes' => array_filter([
                'attributes' => $attributeChanges,
                'properties' => $properties,
            ]),
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    /** @return array{items: list<array<string, mixed>>, pagination: array{current_page: int, last_page: int, total: int}} */
    private function paginated(LengthAwarePaginator $lengthAwarePaginator, callable $map): array
    {
        return ['items' => $lengthAwarePaginator->getCollection()->map($map)->values()->all(), 'pagination' => ['current_page' => $lengthAwarePaginator->currentPage(), 'last_page' => $lengthAwarePaginator->lastPage(), 'total' => $lengthAwarePaginator->total()]];
    }

    /** @return array<string, mixed> */
    private function validateServerInstance(Request $request, ?ServerInstance $serverInstance = null): array
    {
        return $request->validate(['code' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('server_instances', 'code')->ignore($serverInstance?->id)], 'display_name' => ['required', 'string', 'max:100'], 'country_code' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'], 'region' => ['nullable', 'string', 'max:100'], 'ip_address' => ['required', 'ipv4'], 'api_key' => [$serverInstance ? 'nullable' : 'required', 'string', 'min:16', 'max:255'], 'is_active' => ['boolean']]);
    }
}
