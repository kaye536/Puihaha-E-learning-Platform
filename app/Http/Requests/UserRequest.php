<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    public function rules(): array
    {
        $editing = $this->route('user') !== null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user')?->id)],
            'role' => ['required', Rule::in(array_keys(\App\Models\User::staffRoles()))],
            'is_active' => ['nullable', 'boolean'],
            'password' => [$editing ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ];
    }
}
