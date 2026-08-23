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
        ]);
        $paginator = User::query()->with('package')
            ->when($validated['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(fn (Builder $builder): Builder => $builder->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($validated['role'] ?? null, fn (Builder $query, string $role): Builder => $query->where('role', $role))
            ->latest('created_at')
            ->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json(['data' => $this->paginated($paginator, fn (User $user): array => $this->user($user)), 'options' => [
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
        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'package_id' => $validated['package_id'] ?? null,
        ]);

        return response()->json(['data' => $this->user($user->load('package'))], 201);
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

    public function destroyUser(Request $request, User $user, UserDeletionPreparationService $deletionPreparation): JsonResponse
    {
        if ($request->user()?->is($user)) {
            throw ValidationException::withMessages(['user' => ['You cannot delete your own account.']]);
        }
        activity('user')->performedOn($user)->event('delete_requested')->withProperties(['action' => 'admin_user_deletion_requested'])->log('user_delete_requested');
        $deletionPreparation->disableLoginUntilDeletion($user);
        dispatch(new DeleteUser($user));

        return response()->json(status: 204);
    }

    public function packages(Request $request): JsonResponse
    {
        $validated = $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'search' => ['nullable', 'string', 'max:100']]);
        $paginator = Package::query()->withoutGlobalScope('selectable')
            ->when($validated['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('monitoring_limit', 'like', "%{$search}%")->orWhere('price', 'like', "%{$search}%"))
            ->orderBy('price')->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json(['data' => $this->paginated($paginator, fn (Package $package): array => $this->package($package))]);
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
        $validated = $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'search' => ['nullable', 'string', 'max:100']]);
        $paginator = ServerInstance::query()
            ->when($validated['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('code', 'like', "%{$search}%")->orWhere('display_name', 'like', "%{$search}%")->orWhere('ip_address', 'like', "%{$search}%"))
            ->orderBy('code')->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json(['data' => $this->paginated($paginator, fn (ServerInstance $instance): array => $this->serverInstance($instance))]);
    }

    public function storeServerInstance(Request $request): JsonResponse
    {
        $validated = $this->validateServerInstance($request);
        $validated['api_key_hash'] = $validated['api_key'];
        unset($validated['api_key']);
        $instance = ServerInstance::query()->create($validated);

        return response()->json(['data' => $this->serverInstance($instance)], 201);
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
        $validated = $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'search' => ['nullable', 'string', 'max:100']]);
        $paginator = ApiLog::query()->withoutGlobalScope('api_logs')->with('user')
            ->when($validated['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('route', 'like', "%{$search}%")->orWhereHas('user', fn (Builder $users): Builder => $users->where('email', 'like', "%{$search}%")))
            ->latest('created_at')->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json(['data' => $this->paginated($paginator, fn (ApiLog $log): array => ['id' => $log->id, 'route' => $log->route, 'created_at' => $log->created_at?->toIso8601String(), 'user_email' => $log->user?->email])]);
    }

    public function activityLogs(Request $request): JsonResponse
    {
        $validated = $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'search' => ['nullable', 'string', 'max:100']]);
        $paginator = Activity::query()->with('causer')
            ->when($validated['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('description', 'like', "%{$search}%")->orWhere('log_name', 'like', "%{$search}%")->orWhere('event', 'like', "%{$search}%"))
            ->latest('created_at')->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json(['data' => $this->paginated($paginator, fn (Activity $activity): array => ['id' => (string) $activity->id, 'description' => $activity->description, 'log_name' => $activity->log_name, 'event' => $activity->event, 'subject_id' => $activity->subject_id, 'actor' => $activity->causer instanceof User ? $activity->causer->email : null, 'created_at' => $activity->created_at?->toIso8601String()])]);
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
    private function serverInstance(ServerInstance $instance): array
    {
        return ['id' => $instance->id, 'code' => $instance->code, 'display_name' => $instance->display_name, 'country_code' => $instance->country_code, 'region' => $instance->region, 'ip_address' => $instance->ip_address, 'is_active' => $instance->is_active, 'health' => $instance->healthStatus(), 'last_seen_at' => $instance->last_seen_at?->toIso8601String()];
    }

    /** @return array{items: list<array<string, mixed>>, pagination: array{current_page: int, last_page: int, total: int}} */
    private function paginated(LengthAwarePaginator $paginator, callable $map): array
    {
        return ['items' => $paginator->getCollection()->map($map)->values()->all(), 'pagination' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'total' => $paginator->total()]];
    }

    /** @return array<string, mixed> */
    private function validateServerInstance(Request $request, ?ServerInstance $instance = null): array
    {
        return $request->validate(['code' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('server_instances', 'code')->ignore($instance?->id)], 'display_name' => ['required', 'string', 'max:100'], 'country_code' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'], 'region' => ['nullable', 'string', 'max:100'], 'ip_address' => ['required', 'ipv4'], 'api_key' => [$instance ? 'nullable' : 'required', 'string', 'min:16', 'max:255'], 'is_active' => ['boolean']]);
    }
}
