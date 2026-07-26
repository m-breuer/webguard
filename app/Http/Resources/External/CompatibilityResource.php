<?php

declare(strict_types=1);

namespace App\Http\Resources\External;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Preserves the serialized v1 model representation while providing an explicit
 * resource boundary for future API versions.
 *
 * @mixin Model
 */
abstract class CompatibilityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Model $model */
        $model = $this->resource;

        return $model->toArray();
    }
}
