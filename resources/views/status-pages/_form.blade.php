@php
    $componentValues = old(
        'components',
                isset($statusPage)
            ? $statusPage->components->map(
                fn ($component) => [
                    'name' => $component->name,
                    'description' => $component->description,
                    'source_type' => $component->source_type->value,
                    'monitoring_group_id' => $component->monitoring_group_id,
                    'monitoring_group_name' => $component->monitoringGroup?->name,
                    'monitoring_ids' => $component->monitorings->pluck('id')->values()->all(),
                ],
            )->values()->all()
            : $defaultComponents,
    );
@endphp

<x-container>
    @if ($errors->any())
        <div class="mb-4 text-sm text-red-600 dark:text-red-400">
            {{ __('status_page.validation.fix_errors') }}
        </div>
    @endif

    <form method="POST" action="{{ $action }}">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        @if (isset($modal) && $modal)
            <input type="hidden" name="modal_form" value="status-page-{{ isset($statusPage) ? 'edit' : 'create' }}">
        @endif

        <div class="space-y-6"
            x-data="{
                components: @js($componentValues),
                addComponent() {
                    this.components.push({ name: '', description: '', source_type: 'manual', monitoring_group_id: null, monitoring_group_name: null, monitoring_ids: [] });
                },
                removeComponent(index) {
                    this.components.splice(index, 1);
                }
            }">
            <div>
                <x-input-label for="name" :value="__('status_page.form.name')" />
                <x-text-input id="name" name="name" type="text" :value="old('name', $statusPage->name ?? '')" required />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="description" :value="__('status_page.form.description')" />
                <x-textarea id="description" name="description" rows="3">{{ old('description', $statusPage->description ?? '') }}</x-textarea>
                <x-input-error :messages="$errors->get('description')" />
            </div>

            <label class="inline-flex items-center gap-2">
                <x-checkbox-input name="is_public" value="1" :checked="old('is_public', $statusPage->is_public ?? true)" />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('status_page.form.is_public') }}</span>
            </label>

            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-heading type="h2">{{ __('status_page.form.components') }}</x-heading>
                    <x-secondary-button type="button" @click="addComponent()">
                        {{ __('status_page.form.add_component') }}
                    </x-secondary-button>
                </div>
                <x-input-error :messages="$errors->get('components')" />

                <template x-for="(component, index) in components" :key="index">
                    <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                        <input type="hidden" x-bind:name="'components[' + index + '][source_type]'"
                            x-bind:value="component.source_type || 'manual'">
                        <input type="hidden" x-bind:name="'components[' + index + '][monitoring_group_id]'"
                            x-bind:value="component.monitoring_group_id || ''">

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label x-bind:for="'component-name-' + index"
                                    :value="__('status_page.form.component_name')" />
                                <x-text-input x-bind:id="'component-name-' + index" type="text"
                                    x-bind:name="'components[' + index + '][name]'" x-model="component.name"
                                    required />
                            </div>

                            <div x-show="(component.source_type || 'manual') === 'manual'">
                                <x-input-label x-bind:for="'component-monitorings-' + index"
                                    :value="__('status_page.form.monitorings')" />
                                <select x-bind:id="'component-monitorings-' + index"
                                    x-bind:name="'components[' + index + '][monitoring_ids][]'"
                                    x-model="component.monitoring_ids" multiple
                                    x-bind:required="(component.source_type || 'manual') === 'manual'"
                                    class="mt-1 w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    @foreach ($monitorings as $monitoring)
                                        <option value="{{ $monitoring->id }}">
                                            {{ $monitoring->name }} ({{ __('monitoring.types.' . $monitoring->type->value) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="component.source_type === 'monitoring_group'">
                                <x-input-label x-bind:for="'component-group-' + index"
                                    :value="__('status_page.form.monitoring_group')" />
                                <x-text-input x-bind:id="'component-group-' + index" type="text"
                                    x-bind:value="component.monitoring_group_name || ''" readonly />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('status_page.form.monitoring_group_source_help') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <x-input-label x-bind:for="'component-description-' + index"
                                :value="__('status_page.form.component_description')" />
                            <textarea x-bind:id="'component-description-' + index"
                                x-bind:name="'components[' + index + '][description]'" rows="2" x-model="component.description"
                                class="form-textarea mt-1 w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"></textarea>
                        </div>

                        <button type="button" @click="removeComponent(index)"
                            class="mt-3 text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400">
                            {{ __('status_page.form.remove_component') }}
                        </button>
                    </div>
                </template>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-primary-button>
                    {{ $submitLabel }}
                </x-primary-button>
                <x-secondary-button
                    :href="isset($modal) && $modal ? null : route('status-pages.index')"
                    type="button"
                    x-on:click="$dispatch('close-form-modal', 'status-page-form-modal')"
                >
                    {{ __('button.cancel') }}
                </x-secondary-button>
            </div>
        </div>
    </form>
</x-container>
