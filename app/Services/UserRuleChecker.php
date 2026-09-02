<?php

namespace App\Services;

use App\Models\Alias;
use App\Models\EmailData;
use App\Models\Label;
use App\Models\Rule;
use App\Models\User;
use App\Rules\ValidRegex;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class UserRuleChecker
{
    protected $user;

    protected $emailData;

    protected $alias;

    protected $sender;

    protected $subject;

    protected bool $isNewAlias;

    public function __construct(User $user, EmailData $emailData, Alias $alias, bool $isNewAlias = false)
    {
        $this->user = $user;
        $this->emailData = $emailData;
        $this->alias = $alias;
        $this->sender = $emailData->sender;
        $this->subject = $emailData->subject;
        $this->isNewAlias = $isNewAlias;
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
            case 'display_from':
                return $this->conditionSatisfied(base64_decode((string) $this->emailData->display_from), $condition);
            case 'header':
                return $this->headerConditionSatisfied($condition);
            case 'alias_created_by_catch_all':
                return $this->isNewAlias;
            case 'alias_not_created_by_catch_all':
                return ! $this->isNewAlias;
            case 'has_attachments':
                return ! empty($this->emailData->attachments);
            case 'has_no_attachments':
                return empty($this->emailData->attachments);
            case 'email_is_spam':
                return (bool) $this->emailData->isSpam;
            case 'email_is_not_spam':
                return ! $this->emailData->isSpam;
            case 'dmarc_failed':
                return (bool) $this->emailData->failedDmarc;
            case 'dmarc_did_not_fail':
                return ! $this->emailData->failedDmarc;
            case 'email_size':
                return $this->numericConditionSatisfied((int) $this->emailData->size, $condition);
            case 'alias_emails_forwarded':
                return $this->numericConditionSatisfied((int) $this->alias->emails_forwarded, $condition);
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
            return $this->emptyCollectionConditionSatisfied($condition);
        }

        $condition = array_merge($condition, [
            'values' => collect($condition['values'])->map(fn ($value) => strtolower($value))->all(),
        ]);

        return $labelNames->contains(function ($labelName) use ($condition) {
            return $this->conditionSatisfied($labelName, $condition);
        });
    }

    protected function headerConditionSatisfied(array $condition): bool
    {
        $presentHeaderNames = collect($this->emailData->headers ?? [])
            ->map(fn ($header) => strtolower(Str::before($header, ':')))
            ->filter()
            ->unique()
            ->values();

        $wantedHeaderNames = collect($condition['values'] ?? [])
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter()
            ->values();

        if ($wantedHeaderNames->isEmpty()) {
            return false;
        }

        return match ($condition['match']) {
            'exists' => $wantedHeaderNames->contains(
                fn ($name) => $presentHeaderNames->contains($name)
            ),
            'does not exist' => $wantedHeaderNames->every(
                fn ($name) => ! $presentHeaderNames->contains($name)
            ),
            default => false,
        };
    }

    protected function emptyCollectionConditionSatisfied(array $condition): bool
    {
        return in_array($condition['match'], [
            'is not',
            'does not contain',
            'does not start with',
            'does not end with',
            'does not match regex',
        ], true);
    }

    protected function numericConditionSatisfied(int $variable, array $condition): bool
    {
        $values = collect($condition['values'])->map(fn ($value) => (int) $value);

        switch ($condition['match']) {
            case 'is exactly':
                return $values->contains(fn ($value) => $variable === $value);
            case 'is not':
                return ! $values->contains(fn ($value) => $variable === $value);
            case 'is greater than':
                return $values->contains(fn ($value) => $variable > $value);
            case 'is less than':
                return $values->contains(fn ($value) => $variable < $value);
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
                    return ValidRegex::matches((string) $value, (string) $variable);
                });
            case 'does not match regex':
                return ! $values->contains(function ($value) use ($variable) {
                    return ValidRegex::matches((string) $value, (string) $variable);
                });
            default:
                return false;
        }
    }

    /**
     * Static method to get rule IDs for forwards (convenience method)
     */
    public static function getRuleIdsAndActionsForForwards(User $user, EmailData $emailData, Alias $alias, bool $isNewAlias = false): array
    {
        $checker = new self($user, $emailData, $alias, $isNewAlias);

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
    public static function getRuleIdsAndActionsForSends(User $user, EmailData $emailData, Alias $alias, bool $isNewAlias = false): array
    {
        $checker = new self($user, $emailData, $alias, $isNewAlias);

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

    /**
     * Apply alias side-effect actions from matching rules (labels, description, deactivate, delete).
     *
     * Skip when a new catch-all alias is about to be force-deleted on block. Safe to run
     * before quarantine/block exits for existing aliases, and before queueing mail.
     * Pass $includeDelete = false when you still need to update the alias row afterwards.
     */
    public static function applyAliasActionsFromRules(array $ruleIdsAndActions, User $user, Alias $alias, bool $includeDelete = true): void
    {
        $actions = collect($ruleIdsAndActions)->flatten(1);

        $descriptionAction = $actions->firstWhere('type', 'setAliasDescription');

        if ($descriptionAction !== null) {
            $description = trim((string) ($descriptionAction['value'] ?? ''));

            $alias->update([
                'description' => $description === '' ? null : $description,
            ]);
        }

        self::applyAddLabelActions($actions, $user, $alias);
        self::applyRemoveLabelActions($actions, $user, $alias);

        if ($actions->contains('type', 'deactivateAlias')) {
            $alias->deactivate();
        }

        if ($includeDelete) {
            self::applyDeleteAliasActionFromRules($ruleIdsAndActions, $user, $alias);
        }
    }

    public static function applyDeleteAliasActionFromRules(array $ruleIdsAndActions, User $user, Alias $alias): void
    {
        $shouldDelete = collect($ruleIdsAndActions)
            ->flatten(1)
            ->contains('type', 'deleteAlias');

        if (! $shouldDelete || $alias->trashed()) {
            return;
        }

        $alias->delete();
    }

    public static function applyLabelActionsFromRules(array $ruleIdsAndActions, User $user, Alias $alias): void
    {
        self::applyAliasActionsFromRules($ruleIdsAndActions, $user, $alias);
    }

    protected static function applyAddLabelActions($actions, User $user, Alias $alias): void
    {
        $labelNames = $actions
            ->where('type', 'addLabel')
            ->pluck('value')
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->filter()
            ->unique()
            ->values();

        if ($labelNames->isEmpty()) {
            return;
        }

        $attachedIds = $alias->labels()->pluck('labels.id')->all();
        $attachedCount = count($attachedIds);

        foreach ($labelNames as $name) {
            $label = $user->labels()->where('name', $name)->first();

            if (! $label) {
                if ($user->hasReachedLabelLimit()) {
                    continue;
                }

                try {
                    $label = $user->labels()->create([
                        'name' => $name,
                        'colour' => Label::COLOURS[0],
                    ]);
                } catch (QueryException $e) {
                    if ((int) $e->getCode() !== 23000) {
                        throw $e;
                    }

                    $label = $user->labels()->where('name', $name)->first();

                    if (! $label) {
                        continue;
                    }
                }
            }

            if (in_array($label->id, $attachedIds, true)) {
                continue;
            }

            if ($attachedCount >= Label::LABELS_PER_ALIAS_LIMIT) {
                break;
            }

            $alias->labels()->syncWithoutDetaching([$label->id]);
            $attachedIds[] = $label->id;
            $attachedCount++;
        }
    }

    protected static function applyRemoveLabelActions($actions, User $user, Alias $alias): void
    {
        $labelNames = $actions
            ->where('type', 'removeLabel')
            ->pluck('value')
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->filter()
            ->unique()
            ->values();

        if ($labelNames->isEmpty()) {
            return;
        }

        $labelIds = $user->labels()
            ->whereIn('name', $labelNames->all())
            ->pluck('id');

        if ($labelIds->isEmpty()) {
            return;
        }

        $alias->labels()->detach($labelIds->all());
    }
}
