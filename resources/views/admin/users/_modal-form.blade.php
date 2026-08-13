<div class="space-y-6">
    @if (isset($user) && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
        <div class="flex flex-wrap items-center gap-3 rounded-md border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            @if ($user->hasVerifiedEmail())
                <p class="text-sm text-green-600">{{ __('user.messages.email_verified') }}</p>
            @else
                <p class="text-sm text-red-600">{{ __('user.messages.email_unverified') }}</p>
                <form
                    method="POST"
                    action="{{ route('admin.users.verify', [$user, 'modal' => 1]) }}"
                    class="sm:ml-auto"
                >
                    @csrf
                    <x-secondary-button type="submit"> {{ __('user.actions.verify_email') }} </x-secondary-button>
                </form>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ $action }}">
        @if (isset($user))
            @method('PUT')
        @endif
        @include('admin.users._form', ['modalForm' => $modalForm])
    </form>
</div>
