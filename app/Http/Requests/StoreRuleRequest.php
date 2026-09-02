<?php

namespace App\Http\Requests;

use App\Rules\SafeRegex;
use App\Rules\ValidRegex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRuleRequest extends FormRequest
{
    /**
     * Condition types that do not take a match operator or free-text values.
     */
    private const BOOLEAN_CONDITION_TYPES = [
        'alias_created_by_catch_all',
        'alias_not_created_by_catch_all',
        'has_attachments',
        'has_no_attachments',
        'email_is_spam',
        'email_is_not_spam',
        'dmarc_failed',
        'dmarc_did_not_fail',
    ];

    private const NUMERIC_CONDITION_TYPES = [
        'email_size',
        'alias_emails_forwarded',
    ];

    private const STRING_CONDITION_TYPES = [
        'subject',
        'sender',
        'alias',
        'alias_description',
        'alias_label',
        'display_from',
        'header',
    ];

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
     * Normalise boolean conditions and lowercase label action values.
     */
    protected function prepareForValidation(): void
    {
        $conditions = collect($this->input('conditions', []))->map(function ($condition) {
            if (in_array($condition['type'] ?? null, self::BOOLEAN_CONDITION_TYPES, true)) {
                $condition['match'] = 'is exactly';
                $condition['values'] = ['true'];
            }

            if (($condition['type'] ?? null) === 'header' && isset($condition['values']) && is_array($condition['values'])) {
                $condition['values'] = collect($condition['values'])
                    ->map(fn ($value) => strtolower(trim((string) $value)))
                    ->filter()
                    ->values()
                    ->all();
            }

            return $condition;
        })->all();

        $actions = collect($this->input('actions', []))->map(function ($action) {
            if (in_array($action['type'] ?? null, ['addLabel', 'removeLabel'], true) && array_key_exists('value', $action)) {
                $action['value'] = strtolower(trim((string) $action['value']));
            }

            if (($action['type'] ?? null) === 'setAliasDescription' && array_key_exists('value', $action)) {
                $action['value'] = trim((string) $action['value']);
            }

            return $action;
        })->all();

        $this->merge([
            'conditions' => $conditions,
            'actions' => $actions,
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
            'name' => [
                'required',
                'string',
                'max:50',
            ],
            'conditions' => [
                'required',
                'array',
                'max:5',
            ],
            'conditions.*.type' => [
                'required',
                Rule::in(array_merge(
                    self::STRING_CONDITION_TYPES,
                    self::BOOLEAN_CONDITION_TYPES,
                    self::NUMERIC_CONDITION_TYPES,
                )),
            ],
            'conditions.*.match' => Rule::forEach(function ($value, $attribute, $data, $condition) {
                if (in_array($condition['type'] ?? null, self::NUMERIC_CONDITION_TYPES, true)) {
                    return [
                        'required',
                        Rule::in([
                            'is exactly',
                            'is not',
                            'is greater than',
                            'is less than',
                        ]),
                    ];
                }

                if (in_array($condition['type'] ?? null, self::BOOLEAN_CONDITION_TYPES, true)) {
                    return [
                        'required',
                        Rule::in(['is exactly']),
                    ];
                }

                if (($condition['type'] ?? null) === 'header') {
                    return [
                        'required',
                        Rule::in([
                            'exists',
                            'does not exist',
                        ]),
                    ];
                }

                return [
                    'required',
                    Rule::in([
                        'is exactly',
                        'is not',
                        'contains',
                        'does not contain',
                        'starts with',
                        'does not start with',
                        'ends with',
                        'does not end with',
                        'matches regex',
                        'does not match regex',
                    ]),
                ];
            }),
            'conditions.*.values' => Rule::forEach(function ($value, $attribute, $data, $condition) {
                if (in_array($condition['type'] ?? null, self::BOOLEAN_CONDITION_TYPES, true)) {
                    return [
                        'required',
                        'array',
                        'size:1',
                    ];
                }

                if (in_array($condition['type'] ?? null, self::NUMERIC_CONDITION_TYPES, true)) {
                    return [
                        'required',
                        'array',
                        'size:1',
                    ];
                }

                return [
                    'required',
                    'array',
                    'min:1',
                    'max:50',
                ];
            }),
            'conditions.*.values.*' => Rule::forEach(function ($value, $attribute) {
                preg_match('/^conditions\.(\d+)\./', $attribute, $matches);
                $condition = $this->input('conditions.'.($matches[1] ?? ''), []);
                $type = $condition['type'] ?? null;
                $match = $condition['match'] ?? null;

                if (in_array($type, self::BOOLEAN_CONDITION_TYPES, true)) {
                    return [
                        Rule::in(['true']),
                    ];
                }

                if (in_array($type, self::NUMERIC_CONDITION_TYPES, true)) {
                    return [
                        'integer',
                        'min:0',
                        'max:2147483647',
                    ];
                }

                if (in_array($match, ['matches regex', 'does not match regex'], true)) {
                    return [
                        'string',
                        'max:100',
                        new ValidRegex,
                        new SafeRegex,
                        'distinct',
                    ];
                }

                if ($type === 'header') {
                    return [
                        'string',
                        'max:100',
                        'distinct',
                    ];
                }

                return [
                    'string',
                    'max:255',
                    'distinct',
                ];
            }),
            'actions' => [
                'required',
                'array',
                'max:5',
            ],
            'actions.*.type' => Rule::forEach(function ($value, $attribute, $data, $action) {
                $rules = [
                    'required',
                    Rule::in([
                        'subject',
                        'displayFrom',
                        'encryption',
                        'banner',
                        'block',
                        'quarantine',
                        'removeAttachments',
                        'forwardTo',
                        'blocklistSender',
                        'blocklistDomain',
                        'addLabel',
                        'removeLabel',
                        'setAliasDescription',
                        'deactivateAlias',
                        'deleteAlias',
                        // 'webhook',
                    ]),
                ];

                // Allow multiple forwardTo / addLabel / removeLabel actions with different values
                if (! in_array($action['type'], ['forwardTo', 'addLabel', 'removeLabel'], true)) {
                    $rules[] = 'distinct';
                }

                return $rules;
            }),
            'actions.*.value' => Rule::forEach(function ($value, $attribute, $data, $action) {
                if ($action['type'] === 'forwardTo') {
                    return [
                        Rule::in(user()->verifiedRecipients()->pluck('id')->toArray()),
                        'distinct',
                    ]; // Must be a valid verified recipient
                }

                if (in_array($action['type'], ['addLabel', 'removeLabel'], true)) {
                    return [
                        'required',
                        'string',
                        'max:50',
                        'distinct',
                    ];
                }

                if ($action['type'] === 'setAliasDescription') {
                    return [
                        'present',
                        'nullable',
                        'string',
                        'max:200',
                    ];
                }

                if (in_array($action['type'], ['subject', 'displayFrom'], true)) {
                    return [
                        'required',
                        'string',
                        'max:50',
                        'not_regex:/\r|\n/',
                    ];
                }

                return [
                    'required',
                    'max:50',
                ];
            }),
            'operator' => [
                'required',
                'in:AND,OR',
            ],
            'forwards' => 'boolean',
            'replies' => 'boolean',
            'sends' => 'boolean',
        ];
    }
}
