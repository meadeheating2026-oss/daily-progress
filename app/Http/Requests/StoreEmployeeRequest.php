<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|max:255|unique:employees,email',
            'phone'    => 'nullable|string|max:50',
            'position' => 'nullable|string|max:255',
            'user_id'  => 'nullable|exists:users,id',
        ];
    }
}
