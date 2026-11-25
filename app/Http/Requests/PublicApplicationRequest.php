<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validation for the public (guest) membership application form. */
class PublicApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('members', 'email')],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'membership_category_id' => ['required', 'integer', 'exists:membership_categories,id'],
            'membership_level_id' => ['required', 'integer', 'exists:membership_levels,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'consent' => ['accepted'],
            // Anti-spam fields (checked in the controller): honeypot + signed start time.
            'website' => ['nullable', 'string'],
            'form_token' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered. Please contact our office if you need help with your membership.',
            'phone.regex' => 'The phone number may only contain digits, spaces, + - and parentheses.',
            'membership_category_id.required' => 'Please select a membership category.',
            'membership_level_id.required' => 'Please choose a membership level.',
            'consent.accepted' => 'Please confirm that you agree to the use of your details to process this application.',
        ];
    }
}
