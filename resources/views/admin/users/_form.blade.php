@csrf

<div class="mb-4">
    <x-input-label for="name" :value="__('user.fields.name')" />
    <x-text-input id="name" type="text" name="name" :value="old('name', $user->name ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('name')" />
</div>

<div class="mb-4">
    <x-input-label for="email" :value="__('user.fields.email')" />
    <x-text-input id="email" type="email" name="email" :value="old('email', $user->email ?? '')" required />
    <x-input-error :messages="$errors->get('email')" />

    @if (isset($user) && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
        @if ($user->hasVerifiedEmail())
            <p class="mt-2 text-sm text-green-600">
                {{ __('user.messages.email_verified') }}
            </p>
        @else
            <div class="mt-2">
                <p class="text-sm text-red-600">
                    {{ __('user.messages.email_unverified') }}
                </p>
                <form method="POST" action="{{ route('admin.users.verify', $user) }}" class="ml-4">
                    @csrf
                    <x-secondary-button type="submit">
                        {{ __('user.actions.verify_email') }}
                    </x-secondary-button>
                </form>
            </div>
        @endif
    @endif
</div>

<div class="mb-4">
    <x-input-label for="password" :value="__('user.fields.password')" />
    <x-text-input id="password" type="password" name="password" autocomplete="new-password" />
    <x-input-error :messages="$errors->get('password')" />
</div>

<div class="mb-4">
    <x-input-label for="role" :value="__('user.fields.role')" />
    <select id="role" name="role"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
        @foreach (App\Enums\UserRole::cases() as $role)
            <option value="{{ $role->value }}" @selected(old('role', $user->role->value ?? 'regular') === $role->value)>
                {{ ucfirst($role->value) }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('role')" />
</div>

@if (isset($user))
    <div class="mb-4">
        <x-input-label for="package_id" :value="__('user.fields.package')" />
        <select id="package_id" name="package_id"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
            @foreach ($packages as $package)
                <option value="{{ $package->id }}" @selected(old('package_id', $user->package_id) == $package->id)>
                    {{ $package->monitoring_limit }} {{ __('user.fields.monitorings') }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('package_id')" />
    </div>
@endif

<x-primary-button>
    {{ isset($user) ? __('button.update') : __('button.create') }}
</x-primary-button>
