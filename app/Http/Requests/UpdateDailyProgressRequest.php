<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity'      => 'required|numeric|min:0',
            'progress_date' => 'required|date',
            'notes'         => 'nullable|string|max:1000',
        ];
    }
}
