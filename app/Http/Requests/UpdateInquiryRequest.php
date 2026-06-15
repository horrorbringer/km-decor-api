<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(['new', 'contacted', 'qualified', 'quoted', 'won', 'lost'])],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', [
                    'super_admin', 'admin', 'sales_staff',
                ])),
            ],
            'admin_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'quoted_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}
