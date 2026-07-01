<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isGuest = $this->user('sanctum') === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => [$isGuest ? 'required' : 'nullable', 'email', 'max:255'],
            'delivery_method' => ['required', Rule::in(['delivery', 'pickup'])],
            'area' => ['nullable', 'required_if:delivery_method,delivery', 'string', 'max:255'],
            'address' => ['nullable', 'required_if:delivery_method,delivery', 'string', 'max:1000'],
            'map_url' => ['nullable', 'url', 'max:1000'],
            'timing' => ['required', Rule::in(['standard', 'urgent', 'scheduled'])],
            'preferred_date' => ['nullable', 'required_if:timing,scheduled', 'date', 'after_or_equal:today'],
            'support' => ['required', Rule::in(['none', 'unloading', 'installation'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => [$isGuest ? 'required' : 'nullable', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required_with:items', 'uuid', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:9999'],
        ];
    }
}
