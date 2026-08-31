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
        $maxKb = (int) config('payments.receipt_max_kb', 5120);

        return [
            'reference_number' => ['required', 'string', 'max:60'],
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
            'receipt.mimetypes' => 'The receipt must be an image (JPG, PNG, WEBP, or HEIC) or a PDF.',
            'receipt.max' => 'The receipt is too large. Please upload a file under ' . round(((int) config('payments.receipt_max_kb', 5120)) / 1024, 1) . ' MB.',
        ];
    }
}
