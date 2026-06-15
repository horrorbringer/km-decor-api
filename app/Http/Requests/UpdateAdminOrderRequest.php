<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                'pending', 'confirmed', 'processing', 'ready_to_ship',
                'shipped', 'completed', 'cancelled', 'refunded',
            ])],
            'delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
