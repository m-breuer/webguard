<?php

declare(strict_types=1);

namespace App\Http\Requests\StatusPages;

use App\Models\StatusPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatusPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var StatusPage|null $statusPage */
        $statusPage = $this->route('statusPage');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('status_pages', 'slug')->ignore($statusPage?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['boolean'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.name' => ['required', 'string', 'max:255'],
            'components.*.description' => ['nullable', 'string', 'max:1000'],
            'components.*.monitoring_ids' => ['required', 'array', 'min:1'],
            'components.*.monitoring_ids.*' => [
                'required',
                'string',
                Rule::exists('monitorings', 'id')->where('user_id', $this->user()?->id),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => str($this->input('slug') ?: $this->input('name'))->slug()->toString(),
            'is_public' => $this->boolean('is_public'),
            'components' => $this->normalizeComponents(),
        ]);
    }

    /**
     * @return list<array{name: string, description: string|null, monitoring_ids: list<string>}>
     */
    private function normalizeComponents(): array
    {
        $components = $this->input('components', []);

        if (! is_array($components)) {
            return [];
        }

        $normalized = [];

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            $name = mb_trim((string) ($component['name'] ?? ''));
            $description = mb_trim((string) ($component['description'] ?? ''));
            $monitoringIds = $component['monitoring_ids'] ?? [];

            if (! is_array($monitoringIds)) {
                $monitoringIds = [$monitoringIds];
            }

            $monitoringIds = array_values(array_unique(array_filter(
                array_map(static fn (mixed $monitoringId): string => (string) $monitoringId, $monitoringIds),
                static fn (string $monitoringId): bool => $monitoringId !== ''
            )));

            if ($name === '' && $monitoringIds === []) {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'monitoring_ids' => $monitoringIds,
            ];
        }

        return $normalized;
    }
}
