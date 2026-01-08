<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Jobs\DeleteUser;
use App\Models\Package;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
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
     * @return View The view displaying the list of users.
     */
    public function index(Request $request): View
    {
        $lengthAwarePaginator = User::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where(function (Builder $builder) use ($request): void {
                    $builder->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            })->latest()
            ->paginate(10);

        return view('admin.users.index', ['users' => $lengthAwarePaginator]);
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
    public function verify(string $id): RedirectResponse
    {
        $model = User::query()->findOrFail($id);

        if (! $model->hasVerifiedEmail()) {
            $model->markEmailAsVerified();
        }

        return to_route('admin.users.edit', $model->id)->with('success', __('user.messages.user_verified'));
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  string  $id  The ID of the user to delete.
     * @return RedirectResponse A redirect response after deleting the user.
     */
    public function destroy(string $id): RedirectResponse
    {
        $model = User::query()->findOrFail($id);

        if ($model->id === Auth::user()->id) {
            return to_route('admin.users.index')->with('error', __('user.messages.cannot_delete_self'));
        }

        dispatch(new DeleteUser($model));

        return to_route('admin.users.index')->with('success', __('user.messages.user_deleted'));
    }
}
