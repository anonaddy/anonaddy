<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiAuthenticationLoginRequest extends FormRequest
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
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'required|string|max:50',
        ];
    }
}
