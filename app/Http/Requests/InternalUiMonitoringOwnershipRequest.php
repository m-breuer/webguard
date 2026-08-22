<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InternalUiMonitoringOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && ! $this->user()->isDemo();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'team_id' => [
                'required',
                'string',
                Rule::exists('teams', 'id'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    /** @var User $user */
                    $user = $this->user();

                    if (! Team::query()->administeredBy($user)->whereKey((string) $value)->exists()) {
                        $fail(__('team.validation.not_admin'));
                    }
                },
            ],
        ];
    }
}
