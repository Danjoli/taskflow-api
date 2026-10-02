<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Task::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'status' => [
                'sometimes',
                'required',
                Rule::enum(TaskStatus::class),
            ],

            'priority' => [
                'sometimes',
                'required',
                Rule::enum(TaskPriority::class),
            ],

            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'user_id' => ['prohibited'],

            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
        ];
    }
}
