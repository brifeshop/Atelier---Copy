<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage users') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $targetUser = $this->route('user');
        $userId = $targetUser instanceof \App\Models\User ? $targetUser->id : $targetUser;

        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password'              => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'password_confirmation' => ['required_with:password'],
            'roles'                 => ['required', 'array', 'min:1'],
            'roles.*'               => ['string', 'exists:roles,name'],
            'is_active'             => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom validation messages in Indonesian.
     */
    public function messages(): array
    {
        return [
            'name.required'         => 'Nama pengguna wajib diisi.',
            'email.required'        => 'Alamat email wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah digunakan oleh akun lain.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
            'roles.required'        => 'Pilih minimal satu role untuk pengguna ini.',
            'roles.min'             => 'Pilih minimal satu role untuk pengguna ini.',
            'roles.*.exists'        => 'Role yang dipilih tidak valid.',
        ];
    }
}
