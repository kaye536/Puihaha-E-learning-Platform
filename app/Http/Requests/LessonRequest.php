<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:50000'],
            'video_url' => ['nullable', 'url:https', 'max:500'],
            'position' => ['nullable', 'integer', 'between:1,9999'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if (blank($this->input('content')) && blank($this->input('video_url'))) {
                    $validator->errors()->add('content', 'Add some lesson text, a video link, or both.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return ['video_url.url' => 'The video link must be a full https:// address.'];
    }
}
