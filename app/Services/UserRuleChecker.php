<?php

namespace App\Services;

use App\Models\Alias;
use App\Models\EmailData;
use App\Models\User;
use Illuminate\Support\Str;

class UserRuleChecker
{
    protected $user;

    protected $emailData;

    protected $alias;

    protected $sender;

    protected $subject;

    public function __construct(User $user, EmailData $emailData, Alias $alias)
    {
        $this->user = $user;
        $this->emailData = $emailData;
        $this->alias = $alias;
        $this->sender = $emailData->sender;
        $this->subject = $emailData->subject;
    }

    /**
     * Get rule IDs that have satisfied conditions for a specific email type
     */
    protected function getRuleIdsAndActions(string $emailType): array
    {
        $ruleIdsAndActions = [];

        $method = "activeRulesFor{$emailType}Ordered";
        $rules = $this->user->{$method};

        foreach ($rules as $rule) {
            // Check if the conditions of the rule are satisfied
            if ($this->ruleConditionsSatisfied($rule->conditions, $rule->operator)) {
                $ruleIdsAndActions[$rule->id] = $rule->actions;

                // Increment applied count
                $rule->increment('applied', 1, ['last_applied' => now()]);
            }
        }

        return $ruleIdsAndActions;
    }

    /**
     * Check if rule conditions are satisfied
     */
    protected function ruleConditionsSatisfied(array $conditions, string $logicalOperator): bool
    {
        $results = collect();

        foreach ($conditions as $condition) {
            $results->push($this->lookupConditionType($condition));
        }

        $result = $results->unique();

        if ($logicalOperator === 'OR') {
            return $result->contains(true);
        }

        // Logical operator is AND so return false if any conditions are not met
        return ! $result->contains(false);
    }

    /**
     * Look up condition type and check if it's satisfied
     */
    protected function lookupConditionType(array $condition): bool
    {
        switch ($condition['type']) {
            case 'sender':
                return $this->conditionSatisfied($this->emailData->sender, $condition);
            case 'subject':
                return $this->conditionSatisfied(base64_decode($this->emailData->subject), $condition); // Remember to base64_decode any encoded properties of emailData
            case 'alias':
                return $this->conditionSatisfied($this->alias->email, $condition);
            case 'alias_description':
                return $this->conditionSatisfied($this->alias->description, $condition);
            default:
                return false;
        }
    }

    /**
     * Check if a specific condition is satisfied
     */
    protected function conditionSatisfied($variable, array $condition): bool
    {
        $values = collect($condition['values']);

        switch ($condition['match']) {
            case 'is exactly':
                return $values->contains(function ($value) use ($variable) {
                    return $variable === $value;
                });
            case 'is not':
                return ! $values->contains(function ($value) use ($variable) {
                    return $variable === $value;
                });
            case 'contains':
                return $values->contains(function ($value) use ($variable) {
                    return Str::contains($variable, $value);
                });
            case 'does not contain':
                return ! $values->contains(function ($value) use ($variable) {
                    return Str::contains($variable, $value);
                });
            case 'starts with':
                return $values->contains(function ($value) use ($variable) {
                    return Str::startsWith($variable, $value);
                });
            case 'does not start with':
                return ! $values->contains(function ($value) use ($variable) {
                    return Str::startsWith($variable, $value);
                });
            case 'ends with':
                return $values->contains(function ($value) use ($variable) {
                    return Str::endsWith($variable, $value);
                });
            case 'does not end with':
                return ! $values->contains(function ($value) use ($variable) {
                    return Str::endsWith($variable, $value);
                });
            case 'matches regex':
                return $values->contains(function ($value) use ($variable) {
                    return Str::isMatch("/{$value}/", $variable);
                });
            case 'does not match regex':
                return ! $values->contains(function ($value) use ($variable) {
                    return Str::isMatch("/{$value}/", $variable);
                });
            default:
                return false;
        }
    }

    /**
     * Static method to get rule IDs for forwards (convenience method)
     */
    public static function getRuleIdsAndActionsForForwards(User $user, EmailData $emailData, Alias $alias): array
    {
        $checker = new self($user, $emailData, $alias);

        return $checker->getRuleIdsAndActions('Forwards');
    }

    /**
     * Static method to get rule IDs for replies (convenience method)
     */
    public static function getRuleIdsAndActionsForReplies(User $user, EmailData $emailData, Alias $alias): array
    {
        $checker = new self($user, $emailData, $alias);

        return $checker->getRuleIdsAndActions('Replies');
    }

    /**
     * Static method to get rule IDs for sends (convenience method)
     */
    public static function getRuleIdsAndActionsForSends(User $user, EmailData $emailData, Alias $alias): array
    {
        $checker = new self($user, $emailData, $alias);

        return $checker->getRuleIdsAndActions('Sends');
    }

    public static function getRecipientIdsToForwardToFromRuleIdsAndActions($ruleIdsAndActions): array
    {
        // Limit to a total of 10 forwardTo recipients.
        return collect($ruleIdsAndActions)
            ->flatten(1)
            ->where('type', 'forwardTo')
            ->pluck('value')
            ->unique()
            ->take(10)
            ->all();
    }

    public static function shouldBlockEmail($ruleIdsAndActions): bool
    {
        return collect($ruleIdsAndActions)
            ->flatten(1)
            ->contains('type', 'block');
    }
}
