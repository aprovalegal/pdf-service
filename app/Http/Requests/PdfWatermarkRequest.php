<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PdfWatermarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pdf_url'              => 'required|string|url',
            'watermark_url'        => 'required|string|url',
            'watermark_position'   => 'nullable|string|in:top_left,top_right,top_center,bottom_left,bottom_right,bottom_center,middle_left,middle_right,middle_center',
            'watermark_x'          => 'nullable|numeric',
            'watermark_y'          => 'nullable|numeric',
            'watermark_background' => 'nullable|boolean',
            'is_test'              => 'nullable|boolean',
        ];
    }
}
