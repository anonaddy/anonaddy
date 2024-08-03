<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait CheckUserRules
{
    protected $emailType;

    public function checkRules(string $emailType)
    {
        $this->emailType = $emailType;

        $method = "activeRulesFor{$emailType}Ordered";
        $this->user->{$method}->each(function ($rule) {
            // Check if the conditions of the rule are satisfied
            if ($this->ruleConditionsSatisfied($rule->conditions, $rule->operator)) {
                // Apply actions for that rule
                collect($rule->actions)->each(function ($action) {
                    $this->applyAction($action);
                });

                // Increment applied count
                $rule->increment('applied', 1, ['last_applied' => now()]);
            }
        });
    }

    protected function ruleConditionsSatisfied($conditions, $logicalOperator)
    {
        $results = collect();

        collect($conditions)->each(function ($condition) use ($results) {
            $results->push($this->lookupConditionType($condition));
        });

        $result = $results->unique();

        if ($logicalOperator === 'OR') {
            return $result->contains(true);
        }

        // Logical operator is AND so return false if any conditions are not met
        return ! $result->contains(false);
    }

    protected function lookupConditionType($condition)
    {
        switch ($condition['type']) {
            case 'sender':
                return $this->conditionSatisfied($this->sender, $condition);
                break;
            case 'subject':
                return $this->conditionSatisfied($this->subject, $condition);
                break;
            case 'alias':
                return $this->conditionSatisfied($this->alias->email, $condition);
                break;
            case 'alias_description':
                return $this->conditionSatisfied($this->alias->description, $condition);
                break;
        }
    }

    protected function conditionSatisfied($variable, $condition)
    {
        $values = collect($condition['values']);

        switch ($condition['match']) {
            case 'is exactly':
                return $values->contains(function ($value) use ($variable) {
                    return $variable === $value;
                });
                break;
            case 'is not':
                return ! $values->contains(function ($value) use ($variable) {
                    return $variable === $value;
                });
                break;
            case 'contains':
                return $values->contains(function ($value) use ($variable) {
                    return Str::contains($variable, $value);
                });
                break;
            case 'does not contain':
                return ! $values->contains(function ($value) use ($variable) {
                    return Str::contains($variable, $value);
                });
                break;
            case 'starts with':
                return $values->contains(function ($value) use ($variable) {
                    return Str::startsWith($variable, $value);
                });
                break;
            case 'does not start with':
                return ! $values->contains(function ($value) use ($variable) {
                    return Str::startsWith($variable, $value);
                });
                break;
            case 'ends with':
                return $values->contains(function ($value) use ($variable) {
                    return Str::endsWith($variable, $value);
                });
                break;
            case 'does not end with':
                return ! $values->contains(function ($value) use ($variable) {
                    return Str::endsWith($variable, $value);
                });
                break;
            case 'matches regex':
                return $values->contains(function ($value) use ($variable) {
                    return Str::isMatch("/{$value}/", $variable);
                });
                break;
            case 'does not match regex':
                return ! $values->contains(function ($value) use ($variable) {
                    return Str::isMatch("/{$value}/", $variable);
                });
                break;
        }
    }

    protected function applyAction($action)
    {
        switch ($action['type']) {
            case 'subject':
                $this->replacedSubject = ' with subject "'.base64_decode($this->emailSubject).'"';
                $this->email->subject = $action['value'];
                break;
            case 'displayFrom':
                $this->email->from = [];
                $this->email->from($this->fromEmail, $action['value']);
                break;
            case 'encryption':
                if ($action['value'] == false) {
                    if (isset($this->fingerprint)) {
                        $this->fingerprint = null;
                    }
                }
                break;
            case 'banner':
                if (in_array($action['value'], ['top', 'bottom', 'off'])) {

                    if ($this->emailHtml) {
                        // Turn off the banner for the plain text version
                        $this->bannerLocationText = 'off';
                        $this->bannerLocationHtml = $action['value'];
                    } else {
                        $this->bannerLocationText = $action['value'];
                    }
                }
                break;
            case 'block':
                $this->alias->increment('emails_blocked', 1, ['last_blocked' => now()]);
                $this->size = 0;
                exit(0);
                break;
            case 'removeAttachments':
                $this->email->rawAttachments = [];
                break;
            case 'forwardTo':
                // Only apply on forwards
                if ($this->emailType !== 'Forwards') {
                    break;
                }

                $recipient = $this->user->verifiedRecipients()->select(['id', 'email', 'should_encrypt', 'fingerprint'])->find($action['value']);

                if (! $recipient) {
                    break;
                }

                $this->recipientId = $recipient->id;
                $this->fingerprint = $recipient->should_encrypt && ! $this->isAlreadyEncrypted() ? $recipient->fingerprint : null;

                $this->email->to[0]['address'] = $recipient->email;
                break;
            case 'webhook':
                // http payload to url
                break;
        }
    }
}
