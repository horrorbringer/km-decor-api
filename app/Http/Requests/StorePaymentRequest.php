<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in(['bank_transfer', 'cash_on_delivery'])],
            'transaction_ref' => ['nullable', 'string', 'max:255'],
            'proof_url' => ['nullable', 'required_if:method,bank_transfer', 'url', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
