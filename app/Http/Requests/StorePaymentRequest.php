<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reduce the GCash reference to digits only, so "2045 667 982375" and
     * "2045667982375" are stored and compared identically (reliable duplicate
     * detection).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('reference_number')) {
            $this->merge([
                'reference_number' => preg_replace('/\D+/', '', (string) $this->input('reference_number')),
            ]);
        }
    }

    public function rules(): array
    {
        $maxKb = (int) config('payments.receipt_max_kb', 5120);

        return [
            'reference_number' => ['required', 'string', 'digits_between:6,40'],
            'amount_submitted' => ['required', 'numeric', 'min:0'],
            // Accept common screenshot/photo formats — including HEIC/HEIF from
            // iPhones and webp — plus PDF. Content is validated by mime type.
            'receipt' => [
                'required',
                'file',
                'max:' . $maxKb,
                'mimetypes:image/jpeg,image/pjpeg,image/png,image/webp,image/heic,image/heif,application/pdf',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_number.required' => 'Enter the GCash reference number from your receipt.',
            'reference_number.digits_between' => 'Enter a valid GCash reference number (the digits from your receipt).',
            'receipt.mimetypes' => 'The receipt must be an image (JPG, PNG, WEBP, or HEIC) or a PDF.',
            'receipt.max' => 'The receipt is too large. Please upload a file under ' . round(((int) config('payments.receipt_max_kb', 5120)) / 1024, 1) . ' MB.',
        ];
    }
}
