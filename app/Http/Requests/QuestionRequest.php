<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:1000'],
            'options' => ['required', 'array', 'max:4'],
            'options.*' => ['nullable', 'string', 'max:255'],
            'correct_option' => ['required', 'integer', 'between:0,3'],
            'position' => ['nullable', 'integer', 'between:1,9999'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                [$options, $correct] = $this->answerChoices();

                if (count($options) < 2) {
                    $validator->errors()->add('options', 'Give at least two answer choices.');
                } elseif ($correct === null) {
                    $validator->errors()->add('correct_option', 'The choice marked as correct cannot be empty.');
                }
            },
        ];
    }

    /**
     * Blank choices are dropped, so the "correct" index has to follow its text to its new position.
     *
     * @return array{0: list<string>, 1: int|null}
     */
    public function answerChoices(): array
    {
        $kept = [];
        $correct = null;

        foreach ((array) $this->input('options', []) as $index => $text) {
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }
            if ((int) $index === (int) $this->input('correct_option')) {
                $correct = count($kept);
            }
            $kept[] = $text;
        }

        return [$kept, $correct];
    }

    /** Data ready for Question::create / update. */
    public function payload(): array
    {
        [$options, $correct] = $this->answerChoices();

        return [
            'prompt' => $this->validated('prompt'),
            'options' => $options,
            'correct_option' => $correct,
            'position' => $this->validated('position'),
        ];
    }
}
