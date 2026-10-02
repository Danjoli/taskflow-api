<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TaskActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskActivity */
class TaskActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'changes' => $this->changes,
            'actor' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
