<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('view', $this->route('task'));
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'user_id' => ['prohibited'],
            'task_id' => ['prohibited'],
        ];
    }
}
