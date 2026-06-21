<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check()) {
            $user = auth()->user();

            $builder->whereHas('monitoring', function (Builder $builder) use ($user): void {
                $builder->where('user_id', $user->id)
                    ->orWhereHas('team.memberships', function (Builder $builder) use ($user): void {
                        $builder->where('user_id', $user->id);
                    });
            });
        }
    }
}
