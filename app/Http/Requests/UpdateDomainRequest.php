<?php

namespace App\Http\Requests;

use App\Rules\SafeRegex;
use App\Rules\ValidRegex;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDomainRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'description' => 'nullable|max:200',
            'from_name' => 'nullable|string|max:50',
            'auto_create_regex' => [
                'nullable',
                'string',
                'max:100',
                new ValidRegex,
                new SafeRegex,
            ],
        ];
    }
}
