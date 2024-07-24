<?php

namespace App\Http\Requests;

use App\Rules\ValidAliasLocalPart;
use Illuminate\Foundation\Http\FormRequest;

class TestAutoCreateRegexRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resource' => [
                'required',
                'in:username,domain',
            ],
            'id' => [
                'required',
                'uuid',
            ],
            'local_part' => [
                'required',
                new ValidAliasLocalPart,
            ],
        ];
    }
}
