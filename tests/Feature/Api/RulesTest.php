<?php

namespace Tests\Feature\Api;

use App\Mail\ForwardEmail;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\Label;
use App\Models\Rule;
use App\Rules\ValidRegex;
use App\Services\UserRuleChecker;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use PhpMimeMailParser\Parser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RulesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();

        $this->user->defaultUsername->username = 'johndoe';
        $this->user->defaultUsername->save();
    }

    #[Test]
    public function user_can_get_all_rules()
    {
        // Arrange
        Rule::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // At
        $response = $this->json('GET', '/api/v1/rules');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    #[Test]
    public function user_can_get_individual_rule()
    {
        // Arrange
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/rules/'.$rule->id);

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json());
        $this->assertEquals($rule->name, $response->json()['data']['name']);
    }

    #[Test]
    public function user_can_create_new_rule()
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'test rule',
            'conditions' => [
                [
                    'type' => 'sender',
                    'match' => 'is exactly',
                    'values' => [
                        'Test Email',
                    ],
                ],
                [
                    'type' => 'sender',
                    'match' => 'starts with',
                    'values' => [
                        'will',
                    ],
                ],
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'New Subject!',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('test rule', $response->getData()->data->name);
    }

    #[Test]
    public function user_can_create_new_rule_with_quarantine_action()
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'quarantine rule',
            'conditions' => [
                [
                    'type' => 'sender',
                    'match' => 'contains',
                    'values' => [
                        '@example.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'quarantine',
                    'value' => true,
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('quarantine', $response->json('data.actions.0.type'));
    }

    #[Test]
    public function user_can_create_new_rule_with_blocklist_sender_action()
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'blocklist sender rule',
            'conditions' => [
                [
                    'type' => 'sender',
                    'match' => 'contains',
                    'values' => [
                        '@spam.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'blocklistSender',
                    'value' => true,
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('blocklistSender', $response->json('data.actions.0.type'));
    }

    #[Test]
    public function user_can_create_new_rule_with_blocklist_domain_action()
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'blocklist domain rule',
            'conditions' => [
                [
                    'type' => 'sender',
                    'match' => 'contains',
                    'values' => [
                        '@spam.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'blocklistDomain',
                    'value' => true,
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('blocklistDomain', $response->json('data.actions.0.type'));
    }

    #[Test]
    public function user_cannot_create_invalid_rule()
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'invalid rule',
            'conditions' => [
                [
                    'type' => 'invalid',
                    'match' => 'is exactly',
                    'values' => [
                        'Test Email',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'New Subject!',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function user_can_update_rule()
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'operator' => 'AND',
        ]);

        $response = $this->json('PATCH', '/api/v1/rules/'.$rule->id, [
            'name' => 'new name',
            'conditions' => [
                [
                    'type' => 'subject',
                    'match' => 'is exactly',
                    'values' => [
                        'Test Email',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'New Subject!',
                ],
            ],
            'operator' => 'OR',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('new name', $response->getData()->data->name);
        $this->assertEquals('OR', $response->getData()->data->operator);
    }

    #[Test]
    public function user_can_delete_rule()
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('DELETE', '/api/v1/rules/'.$rule->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->rules);
    }

    #[Test]
    public function user_can_activate_rule()
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'active' => false,
        ]);

        $response = $this->json('POST', '/api/v1/active-rules/', [
            'id' => $rule->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->active);
    }

    #[Test]
    public function user_can_deactivate_rule()
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $response = $this->json('DELETE', '/api/v1/active-rules/'.$rule->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->rules[0]->active);
    }

    #[Test]
    public function it_can_apply_user_rules()
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'subject',
                    'match' => 'is exactly',
                    'values' => [
                        'Test Email',
                    ],
                ],
                [
                    'type' => 'sender',
                    'match' => 'starts with',
                    'values' => [
                        'will',
                    ],
                ],
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'New Subject!',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
            'applied' => 0,
            'last_applied' => null,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));

        $sender = 'will@anonaddy.com';

        $size = 1500;

        $emailData = new EmailData($parser, $sender, $size);

        // Check user rules and get rule IDs that have satisfied conditions
        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $ruleIds = array_keys($ruleIdsAndActions);

        $job = new ForwardEmail($alias, $emailData, $this->user->defaultRecipient, false, $ruleIds);

        $email = $job->build();

        $this->assertEquals('New Subject!', $email->subject);

        $this->assertDatabaseHas('rules', [
            'id' => $rule->id,
            'user_id' => $this->user->id,
            'applied' => 1,
            'last_applied' => now(),
        ]);
    }

    #[Test]
    public function user_can_create_rule_with_alias_label_condition(): void
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'label rule',
            'conditions' => [
                [
                    'type' => 'alias_label',
                    'match' => 'is exactly',
                    'values' => [
                        'shopping',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'Labelled Subject',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('alias_label', $response->json('data.conditions.0.type'));
    }

    #[Test]
    public function user_can_create_rule_with_alias_created_by_catch_all_condition(): void
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'catch-all rule',
            'conditions' => [
                [
                    'type' => 'alias_created_by_catch_all',
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'Catch-all Subject',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => true,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('alias_created_by_catch_all', $response->json('data.conditions.0.type'));
        $this->assertEquals('is exactly', $response->json('data.conditions.0.match'));
        $this->assertEquals(['true'], $response->json('data.conditions.0.values'));
    }

    #[Test]
    public function it_applies_rule_when_alias_was_created_by_catch_all(): void
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias_created_by_catch_all',
                    'match' => 'is exactly',
                    'values' => [
                        'true',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'New Catch-all Alias',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
            'applied' => 0,
            'last_applied' => null,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias, true);

        $this->assertArrayHasKey($rule->id, $ruleIdsAndActions);

        $job = new ForwardEmail($alias, $emailData, $this->user->defaultRecipient, false, array_keys($ruleIdsAndActions));
        $email = $job->build();

        $this->assertEquals('New Catch-all Alias', $email->subject);
    }

    #[Test]
    public function it_does_not_apply_catch_all_created_rule_for_existing_alias(): void
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias_created_by_catch_all',
                    'match' => 'is exactly',
                    'values' => [
                        'true',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'New Catch-all Alias',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
            'applied' => 0,
            'last_applied' => null,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias, false);

        $this->assertArrayNotHasKey($rule->id, $ruleIdsAndActions);
    }

    #[Test]
    public function it_applies_rule_when_alias_was_not_created_by_catch_all(): void
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias_not_created_by_catch_all',
                    'match' => 'is exactly',
                    'values' => [
                        'true',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'Existing Alias',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => true,
            'applied' => 0,
            'last_applied' => null,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $forwardRuleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias, false);
        $sendRuleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForSends($this->user, $emailData, $alias, false);

        $this->assertArrayHasKey($rule->id, $forwardRuleIdsAndActions);
        $this->assertArrayHasKey($rule->id, $sendRuleIdsAndActions);
    }

    #[Test]
    public function it_does_not_apply_not_created_by_catch_all_rule_for_new_catch_all_alias(): void
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias_not_created_by_catch_all',
                    'match' => 'is exactly',
                    'values' => [
                        'true',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'Existing Alias',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => true,
            'applied' => 0,
            'last_applied' => null,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $forwardRuleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias, true);
        $sendRuleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForSends($this->user, $emailData, $alias, true);

        $this->assertArrayNotHasKey($rule->id, $forwardRuleIdsAndActions);
        $this->assertArrayNotHasKey($rule->id, $sendRuleIdsAndActions);
    }

    #[Test]
    public function it_applies_rule_when_alias_label_condition_matches(): void
    {
        $label = Label::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'shopping',
        ]);

        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias_label',
                    'match' => 'is exactly',
                    'values' => [
                        'Shopping',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'Labelled Subject',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
            'applied' => 0,
            'last_applied' => null,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);
        $alias->labels()->attach($label->id);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertArrayHasKey($rule->id, $ruleIdsAndActions);

        $job = new ForwardEmail($alias, $emailData, $this->user->defaultRecipient, false, array_keys($ruleIdsAndActions));
        $email = $job->build();

        $this->assertEquals('Labelled Subject', $email->subject);
    }

    #[Test]
    public function it_does_not_apply_rule_when_alias_label_condition_does_not_match(): void
    {
        $label = Label::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'shopping',
        ]);

        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias_label',
                    'match' => 'is exactly',
                    'values' => [
                        'work',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'Labelled Subject',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);
        $alias->labels()->attach($label->id);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertEmpty($ruleIdsAndActions);
    }

    #[Test]
    public function it_can_detect_block_action_for_matching_forward_rule()
    {
        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'block',
                    'value' => true,
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
            'applied' => 0,
            'last_applied' => null,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertNotEmpty($ruleIdsAndActions);
        $this->assertTrue(UserRuleChecker::shouldBlockEmail($ruleIdsAndActions));
        $this->assertFalse(UserRuleChecker::shouldQuarantineEmail($ruleIdsAndActions));
    }

    #[Test]
    public function it_can_detect_quarantine_action_for_matching_forward_rule()
    {
        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'quarantine',
                    'value' => true,
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
            'applied' => 0,
            'last_applied' => null,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertNotEmpty($ruleIdsAndActions);
        $this->assertTrue(UserRuleChecker::shouldQuarantineEmail($ruleIdsAndActions));
        $this->assertFalse(UserRuleChecker::shouldBlockEmail($ruleIdsAndActions));
    }

    #[Test]
    public function it_adds_sender_email_to_blocklist_from_matching_forward_rule()
    {
        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'blocklistSender',
                    'value' => true,
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertNotEmpty($ruleIdsAndActions);

        UserRuleChecker::applyBlocklistActionsFromRules($ruleIdsAndActions, $this->user, $emailData->sender);

        $this->assertDatabaseHas('blocked_senders', [
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => strtolower($emailData->sender),
        ]);
    }

    #[Test]
    public function it_adds_sender_domain_to_blocklist_from_matching_forward_rule()
    {
        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'blocklistDomain',
                    'value' => true,
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertNotEmpty($ruleIdsAndActions);

        UserRuleChecker::applyBlocklistActionsFromRules($ruleIdsAndActions, $this->user, $emailData->sender);

        $domain = Str::afterLast(strtolower($emailData->sender), '@');

        $this->assertDatabaseHas('blocked_senders', [
            'user_id' => $this->user->id,
            'type' => 'domain',
            'value' => $domain,
        ]);
    }

    #[Test]
    public function user_can_create_rule_with_add_label_action(): void
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'label action rule',
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'addLabel',
                    'value' => 'Shopping',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('addLabel', $response->json('data.actions.0.type'));
        $this->assertEquals('shopping', $response->json('data.actions.0.value'));
    }

    #[Test]
    public function it_creates_and_attaches_label_from_matching_rule_action(): void
    {
        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'addLabel',
                    'value' => 'shopping',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertNotEmpty($ruleIdsAndActions);

        UserRuleChecker::applyLabelActionsFromRules($ruleIdsAndActions, $this->user, $alias);

        $this->assertDatabaseHas('labels', [
            'user_id' => $this->user->id,
            'name' => 'shopping',
        ]);

        $label = $this->user->labels()->where('name', 'shopping')->first();

        $this->assertNotNull($label);
        $this->assertTrue($alias->labels()->where('labels.id', $label->id)->exists());
    }

    #[Test]
    public function it_attaches_existing_label_from_matching_rule_action_without_duplicating(): void
    {
        $label = Label::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'shopping',
        ]);

        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'addLabel',
                    'value' => 'Shopping',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        UserRuleChecker::applyLabelActionsFromRules($ruleIdsAndActions, $this->user, $alias);
        UserRuleChecker::applyLabelActionsFromRules($ruleIdsAndActions, $this->user, $alias);

        $this->assertEquals(1, $this->user->labels()->where('name', 'shopping')->count());
        $this->assertEquals(1, $alias->labels()->count());
        $this->assertTrue($alias->labels()->where('labels.id', $label->id)->exists());
    }

    #[Test]
    public function it_matches_boolean_spam_and_attachment_conditions(): void
    {
        $spamRule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'email_is_spam',
                    'match' => 'is exactly',
                    'values' => ['true'],
                ],
            ],
            'actions' => [
                ['type' => 'subject', 'value' => 'Spam'],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $attachmentRule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'has_attachments',
                    'match' => 'is exactly',
                    'values' => ['true'],
                ],
            ],
            'actions' => [
                ['type' => 'subject', 'value' => 'Has Attachment'],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);
        $emailData->isSpam = true;
        $emailData->attachments = [
            ['file_name' => base64_encode('file.pdf')],
        ];

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertArrayHasKey($spamRule->id, $ruleIdsAndActions);
        $this->assertArrayHasKey($attachmentRule->id, $ruleIdsAndActions);
    }

    #[Test]
    public function it_matches_email_size_and_emails_forwarded_numeric_conditions(): void
    {
        $sizeRule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'email_size',
                    'match' => 'is greater than',
                    'values' => [1000],
                ],
            ],
            'actions' => [
                ['type' => 'subject', 'value' => 'Large'],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $forwardedRule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias_emails_forwarded',
                    'match' => 'is exactly',
                    'values' => [5],
                ],
            ],
            'actions' => [
                ['type' => 'subject', 'value' => 'Fifth'],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 5,
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1500);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertArrayHasKey($sizeRule->id, $ruleIdsAndActions);
        $this->assertArrayHasKey($forwardedRule->id, $ruleIdsAndActions);
    }

    #[Test]
    public function it_matches_display_from_and_header_conditions(): void
    {
        $displayFromRule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'display_from',
                    'match' => 'is exactly',
                    'values' => ['Will'],
                ],
            ],
            'actions' => [
                ['type' => 'subject', 'value' => 'From Name'],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $headerRule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'header',
                    'match' => 'exists',
                    'values' => ['list-unsubscribe'],
                ],
            ],
            'actions' => [
                ['type' => 'subject', 'value' => 'Has Unsubscribe'],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertArrayHasKey($displayFromRule->id, $ruleIdsAndActions);
        $this->assertArrayHasKey($headerRule->id, $ruleIdsAndActions);
    }

    #[Test]
    public function it_matches_header_does_not_exist_condition(): void
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'header',
                    'match' => 'does not exist',
                    'values' => ['x-custom-missing'],
                ],
            ],
            'actions' => [
                ['type' => 'subject', 'value' => 'No Custom Header'],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        $this->assertArrayHasKey($rule->id, $ruleIdsAndActions);
    }

    #[Test]
    public function it_applies_deactivate_set_description_remove_label_and_delete_alias_actions(): void
    {
        $label = Label::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'shopping',
        ]);

        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'setAliasDescription',
                    'value' => 'Newsletter signup',
                ],
                [
                    'type' => 'removeLabel',
                    'value' => 'shopping',
                ],
                [
                    'type' => 'deactivateAlias',
                    'value' => true,
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'active' => true,
            'description' => null,
        ]);
        $alias->labels()->attach($label->id);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        UserRuleChecker::applyAliasActionsFromRules($ruleIdsAndActions, $this->user, $alias);

        $alias->refresh();

        $this->assertEquals('Newsletter signup', $alias->description);
        $this->assertFalse($alias->active);
        $this->assertFalse($alias->labels()->where('labels.id', $label->id)->exists());
    }

    #[Test]
    public function it_soft_deletes_alias_from_matching_delete_alias_action(): void
    {
        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'deleteAlias',
                    'value' => true,
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);

        UserRuleChecker::applyAliasActionsFromRules($ruleIdsAndActions, $this->user, $alias);

        $this->assertSoftDeleted('aliases', [
            'id' => $alias->id,
        ]);
    }

    #[Test]
    public function user_can_create_rule_with_new_condition_and_action_types(): void
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'new types rule',
            'conditions' => [
                [
                    'type' => 'email_is_spam',
                ],
                [
                    'type' => 'email_size',
                    'match' => 'is greater than',
                    'values' => [5000],
                ],
                [
                    'type' => 'header',
                    'match' => 'exists',
                    'values' => ['List-Unsubscribe'],
                ],
            ],
            'actions' => [
                [
                    'type' => 'deactivateAlias',
                    'value' => true,
                ],
                [
                    'type' => 'setAliasDescription',
                    'value' => 'Auto description',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('email_is_spam', $response->json('data.conditions.0.type'));
        $this->assertEquals('email_size', $response->json('data.conditions.1.type'));
        $this->assertEquals('deactivateAlias', $response->json('data.actions.0.type'));
        $this->assertEquals('Auto description', $response->json('data.actions.1.value'));
    }

    #[Test]
    public function it_does_not_apply_rules_if_email_type_is_not_selected()
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'subject',
                    'match' => 'is exactly',
                    'values' => [
                        'Test Email',
                    ],
                ],
                [
                    'type' => 'sender',
                    'match' => 'starts with',
                    'values' => [
                        'will',
                    ],
                ],
                [
                    'type' => 'alias',
                    'match' => 'is exactly',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'New Subject!',
                ],
            ],
            'operator' => 'AND',
            'forwards' => false,
            'replies' => true,
            'sends' => true,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));

        $sender = 'will@anonaddy.com';

        $size = 1500;

        $emailData = new EmailData($parser, $sender, $size);

        // Check user rules and get rule IDs that have satisfied conditions
        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);
        $ruleIds = array_keys($ruleIdsAndActions);

        $job = new ForwardEmail($alias, $emailData, $this->user->defaultRecipient, false, $ruleIds);

        $email = $job->build();

        $this->assertEquals($parser->getHeader('subject'), $email->subject);

        $this->assertDatabaseHas('rules', [
            'id' => $rule->id,
            'user_id' => $this->user->id,
            'applied' => 0,
            'last_applied' => null,
        ]);
    }

    #[Test]
    public function it_can_apply_user_rules_in_correct_order()
    {
        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'alias',
                    'match' => 'is not',
                    'values' => [
                        'woot@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'Applied after',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
            'order' => 1,
        ]);

        Rule::factory()->create([
            'user_id' => $this->user->id,
            'conditions' => [
                [
                    'type' => 'subject',
                    'match' => 'is',
                    'values' => [
                        'Test Email',
                    ],
                ],
                [
                    'type' => 'sender',
                    'match' => 'ends with',
                    'values' => [
                        'anonaddy.com',
                    ],
                ],
                [
                    'type' => 'alias',
                    'match' => 'is',
                    'values' => [
                        'ebay@johndoe.anonaddy.com',
                    ],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'New Subject!',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
            'replies' => false,
            'sends' => false,
        ]);

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $parser = $this->getParser(base_path('tests/emails/email.eml'));

        $sender = 'will@anonaddy.com';

        $size = 1000;

        $emailData = new EmailData($parser, $sender, $size);

        // Check user rules and get rule IDs that have satisfied conditions
        $ruleIdsAndActions = UserRuleChecker::getRuleIdsAndActionsForForwards($this->user, $emailData, $alias);
        $ruleIds = array_keys($ruleIdsAndActions);

        $job = new ForwardEmail($alias, $emailData, $this->user->defaultRecipient, false, $ruleIds);

        $email = $job->build();

        $this->assertEquals('Applied after', $email->subject);
    }

    #[Test]
    public function user_can_reorder_rules()
    {
        $ruleOne = Rule::factory()->create([
            'user_id' => $this->user->id,
            'order' => 2,
        ]);

        $ruleTwo = Rule::factory()->create([
            'user_id' => $this->user->id,
            'order' => 0,
        ]);

        $ruleThree = Rule::factory()->create([
            'user_id' => $this->user->id,
            'order' => 1,
        ]);

        $response = $this->json('POST', '/api/v1/reorder-rules/', [
            'ids' => [
                $ruleOne->id,
                $ruleTwo->id,
                $ruleThree->id,
            ],
        ]);

        $this->assertEquals(0, $ruleOne->refresh()->order);
        $this->assertEquals(1, $ruleTwo->refresh()->order);
        $this->assertEquals(2, $ruleThree->refresh()->order);
        $response->assertStatus(200);
    }

    #[Test]
    public function user_cannot_reorder_another_users_rules(): void
    {
        $ownRule = Rule::factory()->create([
            'user_id' => $this->user->id,
            'order' => 0,
        ]);

        $otherUser = $this->createUser('otheruser', 'other@example.com');
        $otherRule = Rule::factory()->create([
            'user_id' => $otherUser->id,
            'order' => 0,
        ]);

        $response = $this->json('POST', '/api/v1/reorder-rules/', [
            'ids' => [
                $ownRule->id,
                $otherRule->id,
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('ids');

        $this->assertEquals(0, $ownRule->refresh()->order);
        $this->assertEquals(0, $otherRule->refresh()->order);
    }

    #[Test]
    public function rule_regex_conditions_reject_redos_prone_patterns(): void
    {
        $dangerousPatterns = ['(.*)*', '(.+)+', '(a+)+', '(\w+)+'];

        foreach ($dangerousPatterns as $pattern) {
            $response = $this->json('POST', '/api/v1/rules', [
                'name' => 'redos rule',
                'conditions' => [
                    [
                        'type' => 'subject',
                        'match' => 'matches regex',
                        'values' => [$pattern],
                    ],
                ],
                'actions' => [
                    [
                        'type' => 'subject',
                        'value' => 'Blocked',
                    ],
                ],
                'operator' => 'AND',
                'forwards' => true,
            ]);

            $response
                ->assertStatus(422)
                ->assertJsonValidationErrorFor('conditions.0.values.0');
        }
    }

    #[Test]
    public function rule_condition_values_have_max_length(): void
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'long value rule',
            'conditions' => [
                [
                    'type' => 'subject',
                    'match' => 'contains',
                    'values' => [str_repeat('a', 256)],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'Blocked',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('conditions.0.values.0');
    }

    #[Test]
    public function rule_regex_conditions_accept_patterns_containing_slashes(): void
    {
        $response = $this->json('POST', '/api/v1/rules', [
            'name' => 'slash regex rule',
            'conditions' => [
                [
                    'type' => 'subject',
                    'match' => 'matches regex',
                    'values' => ['^foo/bar$'],
                ],
            ],
            'actions' => [
                [
                    'type' => 'subject',
                    'value' => 'Matched',
                ],
            ],
            'operator' => 'AND',
            'forwards' => true,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('^foo/bar$', $response->json('data.conditions.0.values.0'));
        $this->assertTrue(ValidRegex::matches('^foo/bar$', 'foo/bar'));
        $this->assertFalse(ValidRegex::matches('^foo/bar$', 'foo-bar'));
    }

    #[Test]
    public function subject_and_display_from_actions_reject_crlf(): void
    {
        foreach (['subject', 'displayFrom'] as $actionType) {
            $response = $this->json('POST', '/api/v1/rules', [
                'name' => 'crlf action rule',
                'conditions' => [
                    [
                        'type' => 'sender',
                        'match' => 'contains',
                        'values' => ['@example.com'],
                    ],
                ],
                'actions' => [
                    [
                        'type' => $actionType,
                        'value' => "Injected\r\nBcc: evil@example.com",
                    ],
                ],
                'operator' => 'AND',
                'forwards' => true,
            ]);

            $response
                ->assertStatus(422)
                ->assertJsonValidationErrorFor('actions.0.value');
        }
    }

    protected function getParser($file)
    {
        $parser = new Parser;

        // Fix some edge cases in from name e.g. "\" John Doe \"" <johndoe@example.com>
        $parser->addMiddleware(function ($mimePart, $next) {
            $part = $mimePart->getPart();

            if (isset($part['headers']['from'])) {
                $value = $part['headers']['from'];
                $value = (is_array($value)) ? $value[0] : $value;

                try {
                    $from = collect(mailparse_rfc822_parse_addresses($value));

                    if ($from->count() > 1) {
                        $part['headers']['from'] = $from->filter(function ($f) {
                            return filter_var($f['address'], FILTER_VALIDATE_EMAIL);
                        })->map(function ($f) {
                            return $f['display'].' <'.$f['address'].'>';
                        })->first();

                        $mimePart->setPart($part);
                    }
                } catch (\Exception $e) {
                    $part['headers']['from'] = str_replace('\\"', '', $part['headers']['from']);
                    $part['headers']['from'] = str_replace('\\', '', $part['headers']['from']);

                    $mimePart->setPart($part);
                }
            }

            if (isset($part['headers']['reply-to'])) {
                $value = $part['headers']['reply-to'];
                $value = (is_array($value)) ? $value[0] : $value;

                try {
                    mailparse_rfc822_parse_addresses($value);
                } catch (\Exception $e) {
                    $part['headers']['reply-to'] = '<'.Str::afterLast($part['headers']['reply-to'], '<');

                    $mimePart->setPart($part);
                }
            }

            return $next($mimePart);
        });

        if ($file === 'stream') {
            $fd = fopen('php://stdin', 'r');
            $rawEmail = '';
            while (! feof($fd)) {
                $rawEmail .= fread($fd, 1024);
            }
            fclose($fd);
            $parser->setText($rawEmail);
        } else {
            $parser->setPath($file);
        }

        return $parser;
    }
}
