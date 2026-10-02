<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('task'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'required', Rule::enum(TaskPriority::class)],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'user_id' => ['prohibited'],
            'project_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('projects', 'id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'tag_ids' => ['sometimes', 'array', 'max:20'],
            'tag_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('tags', 'id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::notIn([$this->route('task')->id]),
                Rule::exists('tasks', 'id')->where(
                    fn ($query) => $query
                        ->where('user_id', $this->user()->id)
                        ->whereNull('parent_id')
                ),
            ],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $this->filled('parent_id')
                    && $this->route('task')->subtasks()->exists()
                ) {
                    $validator->errors()->add(
                        'parent_id',
                        'A task with subtasks cannot become a subtask.'
                    );
                }
            },
        ];
    }
}
