<x-app-layout>
    <x-slot name="header">
        <x-heading>{{ __('user.actions.edit') . ': ' . $user->name }}</x-heading>

        <x-secondary-button :href="route('admin.users.index')" class="sm:ml-auto">
            {{ __('button.back') }}
        </x-secondary-button>
    </x-slot>

    <x-main>
        <x-container>
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
                <div class="mb-6 flex flex-wrap items-center gap-3 rounded-md border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                    @if ($user->hasVerifiedEmail())
                        <p class="text-sm text-green-600">
                            {{ __('user.messages.email_verified') }}
                        </p>
                    @else
                        <p class="text-sm text-red-600">
                            {{ __('user.messages.email_unverified') }}
                        </p>

                        <form method="POST" action="{{ route('admin.users.verify', $user) }}" class="sm:ml-auto">
                            @csrf
                            <x-secondary-button type="submit">
                                {{ __('user.actions.verify_email') }}
                            </x-secondary-button>
                        </form>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @method('PUT')
                @include('admin.users._form')
            </form>
        </x-container>
    </x-main>
</x-app-layout>
