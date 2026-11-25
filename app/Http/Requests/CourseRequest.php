<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'membership_level_id' => ['nullable', 'integer', 'exists:membership_levels,id'],
            'pass_mark' => ['required', 'integer', 'between:1,100'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    /** Validated data with the checkbox turned into a real boolean. */
    public function payload(): array
    {
        return array_merge($this->validated(), ['is_published' => $this->boolean('is_published')]);
    }
}
