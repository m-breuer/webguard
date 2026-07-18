@csrf
@if (isset($team))
    @method('PATCH')
@endif
@if (!empty($modal))
    <input type="hidden" name="modal_form" value="{{ $modalForm }}">
@endif

<div class="space-y-4">
    <div>
        <x-input-label for="name" :value="__('team.fields.name')" />
        <x-text-input id="name" type="text" name="name" :value="old('name', $team->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="description" :value="__('team.fields.description')" />
        <textarea id="description" name="description" rows="4"
            class="mt-1 w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">{{ old('description', $team->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" />
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <x-primary-button>
            {{ __('button.save') }}
        </x-primary-button>

        @if (!empty($modal))
            <x-secondary-button type="button" x-on:click="$dispatch('close-form-modal', 'team-form-modal')">
                {{ __('button.cancel') }}
            </x-secondary-button>
        @endif
    </div>
</div>
