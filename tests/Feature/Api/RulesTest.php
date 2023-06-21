<?php

namespace Tests\Feature\Api;

use App\Mail\ForwardEmail;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\Rule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use PhpMimeMailParser\Parser;
use Tests\TestCase;

class RulesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();

        $this->user->recipients()->save($this->user->defaultRecipient);
        $this->user->usernames()->save($this->user->defaultUsername);
        $this->user->defaultUsername->username = 'johndoe';
        $this->user->defaultUsername->save();
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function user_can_delete_rule()
    {
        $rule = Rule::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('DELETE', '/api/v1/rules/'.$rule->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->rules);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
    public function it_can_apply_user_rules()
    {
        Rule::factory()->create([
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

        $job = new ForwardEmail($alias, $emailData, $this->user->defaultRecipient);

        $email = $job->build();

        $this->assertEquals('New Subject!', $email->subject);
    }

    /** @test */
    public function it_does_not_apply_rules_if_email_type_is_not_selected()
    {
        Rule::factory()->create([
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

        $job = new ForwardEmail($alias, $emailData, $this->user->defaultRecipient);

        $email = $job->build();

        $this->assertEquals($parser->getHeader('subject'), $email->subject);
    }

    /** @test */
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

        $job = new ForwardEmail($alias, $emailData, $this->user->defaultRecipient);

        $email = $job->build();

        $this->assertEquals('Applied after', $email->subject);
    }

    /** @test */
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

    protected function getParser($file)
    {
        $parser = new Parser();

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

        if ($file == 'stream') {
            $fd = fopen('php://stdin', 'r');
            $this->rawEmail = '';
            while (! feof($fd)) {
                $this->rawEmail .= fread($fd, 1024);
            }
            fclose($fd);
            $parser->setText($this->rawEmail);
        } else {
            $parser->setPath($file);
        }

        return $parser;
    }
}
