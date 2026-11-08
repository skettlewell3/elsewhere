<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\Attributes\ErrorBag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

#[ErrorBag('register')]
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'last_name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8),
            ],

            'invite_code' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim(
                (string) $this->input('first_name', '')
            ),
            'last_name' => trim(
                (string) $this->input('last_name', '')
            ),
            'email' => strtolower(trim(
                (string) $this->input('email', '')
            )),
            'invite_code' => trim(
                (string) $this->input('invite_code', '')
            ),
        ]);
    }

    public function messages(): array
    {
        return [
            'password.confirmed' =>
                'The passwords do not match.',

            'invite_code.required' =>
                'A sign-up code is required to create an Elsewhere account.',
        ];
    }
}
