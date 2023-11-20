<?php

namespace App\Http\Requests;

use App\Enums\DisplayFromFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDisplayFromFormatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'format' => [
                'required',
                'integer',
                Rule::in(array_column(DisplayFromFormat::cases(), 'value')),
            ],
        ];
    }
}
