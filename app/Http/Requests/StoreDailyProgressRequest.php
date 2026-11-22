<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'      => 'required|exists:projects,id',
            'project_item_id' => 'required|exists:project_items,id',
            'employee_id'     => 'required|exists:employees,id',
            'progress_date'   => 'required|date',
            'quantity'        => 'required|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required'      => __('general.project_required'),
            'project_item_id.required' => __('general.project_item_required'),
            'employee_id.required'     => __('general.employee_required'),
            'progress_date.required'   => __('general.date_required'),
            'quantity.required'        => __('general.quantity_required'),
            'quantity.min'             => __('general.quantity_must_be_positive'),
        ];
    }
}
