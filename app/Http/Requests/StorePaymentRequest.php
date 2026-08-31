<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:60'],
            'amount_submitted' => ['required', 'numeric', 'min:0'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:' . config('payments.receipt_max_kb', 5120)],
        ];
    }
}
