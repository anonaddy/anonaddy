<?php

namespace App\Services;

use App\Models\Alias;
use App\Models\EmailData;
use App\Models\Rule;
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
        $matchedRuleIds = [];

        $method = "activeRulesFor{$emailType}Ordered";
        $rules = $this->user->{$method};

        foreach ($rules as $rule) {
            // Check if the conditions of the rule are satisfied
            if ($this->ruleConditionsSatisfied($rule->conditions, $rule->operator)) {
                $ruleIdsAndActions[$rule->id] = $rule->actions;

                $matchedRuleIds[] = $rule->id;
            }
        }

        if (! empty($matchedRuleIds)) {
            Rule::whereIn('id', $matchedRuleIds)->increment('applied', 1, ['last_applied' => now()]);
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
            case 'alias_label':
                return $this->aliasLabelConditionSatisfied($condition);
            default:
                return false;
        }
    }

    protected function aliasLabelConditionSatisfied(array $condition): bool
    {
        if (! $this->alias->relationLoaded('labels')) {
            $this->alias->load('labels');
        }

        $labelNames = $this->alias->labels->pluck('name');

        if ($labelNames->isEmpty()) {
            return $this->emptyAliasLabelConditionSatisfied($condition);
        }

        $condition = array_merge($condition, [
            'values' => collect($condition['values'])->map(fn ($value) => strtolower($value))->all(),
        ]);

        return $labelNames->contains(function ($labelName) use ($condition) {
            return $this->conditionSatisfied($labelName, $condition);
        });
    }

    protected function emptyAliasLabelConditionSatisfied(array $condition): bool
    {
        return in_array($condition['match'], [
            'is not',
            'does not contain',
            'does not start with',
            'does not end with',
            'does not match regex',
        ], true);
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

    public static function shouldQuarantineEmail($ruleIdsAndActions): bool
    {
        return collect($ruleIdsAndActions)
            ->flatten(1)
            ->contains('type', 'quarantine');
    }

    /**
     * Add matching blocklist rule actions for the given sender to the user's blocklist.
     *
     * Intended for forwarded (inbound) mail where $sender is the original From address.
     */
    public static function applyBlocklistActionsFromRules(array $ruleIdsAndActions, User $user, ?string $sender): void
    {
        $sender = strtolower(trim((string) $sender));

        if ($sender === '' || $sender === '<>' || ! str_contains($sender, '@')) {
            return;
        }

        $actions = collect($ruleIdsAndActions)->flatten(1);

        if ($actions->contains('type', 'blocklistSender') && filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            $user->blockedSenders()->firstOrCreate([
                'type' => 'email',
                'value' => $sender,
            ]);
        }

        if ($actions->contains('type', 'blocklistDomain')) {
            $domain = substr($sender, strrpos($sender, '@') + 1);

            if ($domain !== '' && preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain)) {
                $user->blockedSenders()->firstOrCreate([
                    'type' => 'domain',
                    'value' => $domain,
                ]);
            }
        }
    }
}
