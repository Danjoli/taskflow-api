<?php

declare(strict_types=1);

namespace App\Http\Requests\Tag;

use App\Models\Tag;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Tag::class);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tags', 'name')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'user_id' => ['prohibited'],
        ];
    }
}
