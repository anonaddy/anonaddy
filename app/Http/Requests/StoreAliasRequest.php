<?php

namespace App\Http\Requests;

use App\Models\Alias;
use App\Models\Label;
use App\Rules\UserLabelId;
use App\Rules\ValidAliasLocalPart;
use App\Rules\VerifiedRecipientId;
use Closure;
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
        $prefix = Str::before($this->local_part ?? '', '+');
        $domain = strtolower((string) $this->domain);
        $localPartWithoutExtension = $prefix;

        if ($this->input('format') === 'custom' && $this->user()->appendsRandomCharactersToCustomAliases()) {
            $localPartWithoutExtension = $this->user()->localPartWithCustomRandomCharacters($prefix, $domain);
        }

        $this->merge([
            'domain' => $domain,
            'local_part_without_extension' => $localPartWithoutExtension,
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
            'format' => 'nullable|in:random_characters,uuid,random_words,random_male_name,random_female_name,random_noun,custom',
            'recipient_ids' => [
                'bail',
                'nullable',
                'array',
                'max:10',
                new VerifiedRecipientId,
            ],
            'label_ids' => [
                'bail',
                'nullable',
                'array',
                'max:'.Label::LABELS_PER_ALIAS_LIMIT,
                new UserLabelId,
            ],
            'label_ids.*' => 'required|uuid|distinct',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('local_part_without_extension', [
            'bail',
            'required',
            'max:64',
            function (string $attribute, mixed $value, Closure $fail): void {
                $email = strtolower($value.'@'.$this->validationData()['domain']);

                if (Alias::withTrashed()->where('email', $email)->exists()) {
                    $fail('That alias already exists.');
                }
            },
            new ValidAliasLocalPart,
        ], function () {
            $format = $this->validationData()['format'] ?? 'random_characters';

            return $format === 'custom';
        });
    }
}
