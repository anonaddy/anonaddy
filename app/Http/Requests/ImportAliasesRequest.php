<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ImportAliasesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'aliases_import' => [
                'required',
                File::types('csv')->min(0.1)->max(1024), // 1MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'aliases_import' => 'Please check that your file type is CSV, make sure that it has at least 2 rows of aliases and is less than 1MB in size.',
        ];
    }
}
