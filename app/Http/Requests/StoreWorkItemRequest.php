<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'category_id' => 'nullable|exists:work_item_categories,id',
            'unit'        => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'shift_type'  => 'nullable|string|in:day,night,both',
            'lag'         => 'nullable|integer|min:0',
        ];
    }
}
