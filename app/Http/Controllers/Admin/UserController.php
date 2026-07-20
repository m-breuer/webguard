<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Jobs\DeleteUser;
use App\Models\Package;
use App\Models\User;
use App\Services\UserDeletionPreparationService;
use App\Support\Admin\AsyncTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Class UserController
 *
 * Handles administrative operations for managing user accounts.
 */
class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @param  Request  $request  The HTTP request instance, potentially containing a 'search' parameter.
     * @return View|JsonResponse The view displaying the list of users or async table rows.
     */
    public function index(Request $request): View|JsonResponse
    {
        $validated = $request->validate(AsyncTable::requestRules([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'in:' . implode(',', UserRole::values())],
            'email_verification' => ['nullable', 'string', 'in:verified,unverified'],
            'package_id' => ['nullable', 'string', 'exists:packages,id'],
        ], ['name', 'email', 'email_verified_at', 'role', 'monitoring_limit', 'created_at', 'updated_at']));
        $asyncTableOptions = AsyncTable::options($validated, 'created_at', 'desc', 10);

        $query = User::query()
            ->with('package')
            ->select('users.*')
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('users.name', 'like', '%' . $search . '%')
                        ->orWhere('users.email', 'like', '%' . $search . '%')
                        ->orWhere('users.role', 'like', '%' . $search . '%')
                        ->orWhereHas('package', function (Builder $builder) use ($search): void {
                            $builder->where('monitoring_limit', 'like', '%' . $search . '%')
                                ->orWhere('price', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($validated['role'] ?? null, fn (Builder $builder, string $role): Builder => $builder->where('users.role', $role))
            ->when($validated['email_verification'] ?? null, function (Builder $builder, string $verification): Builder {
                return $verification === 'verified'
                    ? $builder->whereNotNull('users.email_verified_at')
                    : $builder->whereNull('users.email_verified_at');
            })
            ->when($validated['package_id'] ?? null, fn (Builder $builder, string $packageId): Builder => $builder->where('users.package_id', $packageId));

        if ($asyncTableOptions->sort === 'monitoring_limit') {
            $query->leftJoin('packages as sort_packages', 'sort_packages.id', '=', 'users.package_id')
                ->orderBy('sort_packages.monitoring_limit', $asyncTableOptions->direction)
                ->latest('users.created_at');
        } else {
            $query->orderBy('users.' . $asyncTableOptions->sort, $asyncTableOptions->direction)
                ->orderBy('users.id');
        }

        $lengthAwarePaginator = $query->paginate($asyncTableOptions->perPage);

        if ($request->expectsJson()) {
            return AsyncTable::json($lengthAwarePaginator, 'admin.users.partials.rows', ['users' => $lengthAwarePaginator]);
        }

        $packages = Package::query()->withoutGlobalScope('selectable')->orderBy('monitoring_limit')->get();
        $modalForm = $request->string('modal')->toString();
        $modalUser = null;

        if ($modalForm === 'admin-user-edit' && $request->filled('user')) {
            $modalUser = User::query()->findOrFail($request->string('user')->toString());
        }

        $filters = [
            [
                'name' => 'role',
                'label' => __('user.fields.role'),
                'placeholder' => __('search.filter.text', ['attribute' => __('user.fields.role')]),
                'options' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $userRole): array => [$userRole->value => ucfirst($userRole->value)])->all(),
            ],
            [
                'name' => 'email_verification',
                'label' => __('user.fields.email_verification'),
                'placeholder' => __('search.filter.text', ['attribute' => __('user.fields.email_verification')]),
                'options' => [
                    'verified' => __('user.messages.email_verified'),
                    'unverified' => __('user.messages.email_unverified'),
                ],
            ],
            [
                'name' => 'package_id',
                'label' => __('user.fields.package'),
                'placeholder' => __('search.filter.text', ['attribute' => __('user.fields.package')]),
                'options' => $packages
                    ->mapWithKeys(fn (Package $package): array => [
                        $package->id => __('user.fields.monitoring_limit') . ': ' . $package->monitoring_limit,
                    ])
                    ->all(),
            ],
        ];

        return view('admin.users.index', [
            'users' => $lengthAwarePaginator,
            'filters' => $filters,
            'activeFilters' => [
                'role' => (string) ($validated['role'] ?? ''),
                'email_verification' => (string) ($validated['email_verification'] ?? ''),
                'package_id' => (string) ($validated['package_id'] ?? ''),
            ],
            'sort' => $asyncTableOptions->sort,
            'direction' => $asyncTableOptions->direction,
            'modalForm' => $modalForm,
            'modalUser' => $modalUser,
            'modalPackages' => Package::all(),
        ]);
    }

    /**
     * Show the form for creating a new user.
     *
     * @return View The view for creating a new user.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  Request  $storeUserRequest  The HTTP request instance containing user data.
     * @return RedirectResponse A redirect response after storing the user.
     */
    public function store(StoreUserRequest $storeUserRequest): RedirectResponse
    {
        $validated = $storeUserRequest->validated();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
        ]);

        return to_route('admin.users.index')->with('success', __('user.messages.user_created'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  string  $id  The ID of the user to edit.
     * @return View The view for editing the user.
     */
    public function edit(string $id): View
    {
        $model = User::query()->findOrFail($id);

        return view('admin.users.edit', ['user' => $model, 'packages' => Package::all()]);
    }

    /**
     * Update the specified user in storage.
     *
     * @param  Request  $updateUserRequest  The HTTP request instance containing updated user data.
     * @param  string  $id  The ID of the user to update.
     * @return RedirectResponse A redirect response after updating the user.
     */
    public function update(UpdateUserRequest $updateUserRequest, string $id): RedirectResponse
    {
        $model = User::query()->findOrFail($id);

        $validated = $updateUserRequest->validated();

        $model->name = $validated['name'];
        $model->email = $validated['email'];
        $model->role = $validated['role'];
        $model->package_id = $validated['package_id'];

        if (! empty($validated['password'])) {
            $model->password = bcrypt($validated['password']);
        }

        $model->save();

        return to_route('admin.users.index')->with('success', __('user.messages.user_updated'));
    }

    /**
     * Mark the specified user's email as verified.
     *
     * @param  string  $id  The ID of the user to verify.
     * @return RedirectResponse A redirect response after verifying the user.
     */
    public function verify(Request $request, string $id): RedirectResponse
    {
        $model = User::query()->findOrFail($id);

        if (! $model->hasVerifiedEmail()) {
            $model->markEmailAsVerified();
        }

        if ($request->boolean('modal')) {
            return to_route('admin.users.index', [
                'modal' => 'admin-user-edit',
                'user' => $model->id,
            ])->with('success', __('user.messages.user_verified'));
        }

        return to_route('admin.users.edit', $model->id)->with('success', __('user.messages.user_verified'));
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  string  $id  The ID of the user to delete.
     * @return RedirectResponse A redirect response after deleting the user.
     */
    public function destroy(string $id, UserDeletionPreparationService $userDeletionPreparationService): RedirectResponse
    {
        $model = User::query()->findOrFail($id);

        if ($model->id === Auth::user()->id) {
            return to_route('admin.users.index')->with('error', __('user.messages.cannot_delete_self'));
        }

        activity('user')
            ->performedOn($model)
            ->event('delete_requested')
            ->withProperties(['action' => 'admin_user_deletion_requested'])
            ->log('user_delete_requested');

        $userDeletionPreparationService->disableLoginUntilDeletion($model);

        dispatch(new DeleteUser($model));

        return to_route('admin.users.index')->with('success', __('user.messages.user_deleted'));
    }
}
