<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\PubliclyRoutableUrl as PubliclyRoutableUrlPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PubliclyRoutableUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! PubliclyRoutableUrlPolicy::allows((string) $value, resolveDns: true)) {
            $fail(__('validation.url'));
        }
    }
}
