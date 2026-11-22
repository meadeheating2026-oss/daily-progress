<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handle authorization via middleware or policy
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255|unique:project_templates,name',
            'description' => 'nullable|string',
            'type'        => 'nullable|string|max:100',
        ];
    }
}
