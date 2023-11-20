<?php

namespace App\Http\Requests;

use App\Rules\ValidAliasLocalPart;
use App\Rules\VerifiedRecipientId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAliasRequest extends FormRequest
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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => strtolower($this->domain),
            'local_part_without_extension' => Str::before($this->local_part, '+'), // Remove extension so that we can check alias uniqueness properly
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'domain' => [
                'required',
                'string',
                Rule::in($this->user()->domainOptions()),
            ],
            'description' => 'nullable|max:200',
            'format' => 'nullable|in:random_characters,uuid,random_words,custom',
            'recipient_ids' => [
                'nullable',
                'array',
                'max:10',
                new VerifiedRecipientId(),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('local_part_without_extension', [
            'required',
            'max:50',
            Rule::unique('aliases', 'local_part')->where(function ($query) {
                return $query->where('domain', $this->validationData()['domain']);
            }),
            new ValidAliasLocalPart(),
        ], function () {
            $format = $this->validationData()['format'] ?? 'random_characters';

            return $format === 'custom';
        });
    }

    public function messages()
    {
        return [
            'local_part_without_extension.unique' => 'That alias already exists.',
        ];
    }
}
