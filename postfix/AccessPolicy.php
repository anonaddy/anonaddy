<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Database;
use ParagonIE\ConstantTime\Base32;

try {
    $repository = Dotenv\Repository\RepositoryBuilder::createWithDefaultAdapters()
        ->allowList([
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'DB_SOCKET',
            'MYSQL_ATTR_SSL_CA',
            'ACTION_NON_ASCII',
            'ACTION_DOES_NOT_EXIST',
            'ACTION_ALIAS_DISCARD',
            'ACTION_USERNAME_DISCARD',
            'ACTION_DOMAIN_DISCARD',
            'ACTION_REJECT',
            'ACTION_DEFER',
            'ACTION_DEFER_NEW',
            'ANONADDY_ALL_DOMAINS',
            'ANONADDY_SECRET',
            'ANONADDY_ADMIN_USERNAME',
        ])
        ->make();

    $dotenv = Dotenv\Dotenv::create($repository, dirname(__DIR__));
    $dotenv->load();

    $database = new Database();

    $database->addConnection([
        'driver' => 'mysql',
        'read' => [
            'host' => [
                $_ENV['DB_HOST'] ?? '127.0.0.1',
            ],
        ],
        'write' => [
            'host' => [
                $_ENV['DB_HOST'] ?? '127.0.0.1',
            ],
        ],
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? 'forge',
        'username' => $_ENV['DB_USERNAME'] ?? 'forge',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'unix_socket' => $_ENV['DB_SOCKET'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => $_ENV['MYSQL_ATTR_SSL_CA'] ?? null,
        ]) : [],
        'sticky' => true,
    ]);

    // Make this instance available globally via static methods
    $database->setAsGlobal();

    // Define actions, these can be overridden by adding the variables to your .env file
    // e.g. ACTION_DOES_NOT_EXIST='550 5.1.1 User not found'
    define('ACTION_NON_ASCII', $_ENV['ACTION_NON_ASCII'] ?? '553 5.6.7 Non-ASCII characters in the local-part of the recipient address are not permitted');
    define('ACTION_DOES_NOT_EXIST', $_ENV['ACTION_DOES_NOT_EXIST'] ?? '550 5.1.1 Address does not exist');
    define('ACTION_ALIAS_DISCARD', $_ENV['ACTION_ALIAS_DISCARD'] ?? 'DISCARD is inactive alias');
    define('ACTION_USERNAME_DISCARD', $_ENV['ACTION_USERNAME_DISCARD'] ?? 'DISCARD has inactive username');
    define('ACTION_DOMAIN_DISCARD', $_ENV['ACTION_DOMAIN_DISCARD'] ?? 'DISCARD has inactive domain');
    define('ACTION_REJECT', $_ENV['ACTION_REJECT'] ?? '552 5.2.2 User over quota');
    define('ACTION_DEFER', $_ENV['ACTION_DEFER'] ?? '452 4.2.2 User over quota');
    define('ACTION_DEFER_NEW', $_ENV['ACTION_DEFER_NEW'] ?? '450 4.2.1 User over quota');

    $args = getArgs();

    $allDomains = explode(',', $_ENV['ANONADDY_ALL_DOMAINS'] ?? '');

    $adminUsername = $_ENV['ANONADDY_ADMIN_USERNAME'] ?? null;

    $aliasEmail = strtolower($args['recipient']);

    // If no alias email is provided then exit
    if (empty($aliasEmail) || empty($allDomains)) {
        sendAction(ACTION_DOES_NOT_EXIST);

        logData('No alias email or $allDomains not set.');
        exit(0);
    }

    //$senderEmail = strtolower($args['sender']);
    [$aliasLocalPart, $aliasDomain] = explode('@', $aliasEmail);

    if (! mb_check_encoding($aliasLocalPart, 'ASCII')) {
        sendAction(ACTION_NON_ASCII);

        // exit to prevent running the rest of the script
        exit(0);
    }

    $aliasHasSharedDomain = in_array($aliasDomain, $allDomains);

    // Check if it is a bounce with a valid VERP...
    if (substr($aliasEmail, 0, 2) === 'b_') {
        if ($outboundMessageId = getIdFromVerp($aliasLocalPart, $aliasEmail)) {
            // Is a valid bounce
            $outboundMessage = Database::table('outbound_messages')->find($outboundMessageId);

            // If there is no outbound message found or no alias_id then reject since we cannot forward this to the user
            if (is_null($outboundMessage) || (is_null($outboundMessage?->alias_id) && $outboundMessage->bounced)) {
                // Must have been more than 7 days or a notification that has already bounced
                sendAction(ACTION_DOES_NOT_EXIST);
            } else {
                // Allow through, may be an auto-reply etc.
                sendAction('DUNNO');
            }

            // exit to prevent running the rest of the script
            exit(0);
        }
    }

    // Check if the alias has a username subdomain
    $aliasHasUsernameDomain = ! empty(array_filter($allDomains, fn ($domain) => endsWith($aliasDomain, ".{$domain}")));

    // If the alias has a plus extension then remove it
    if (str_contains($aliasEmail, '+')) {
        $aliasEmail = before($aliasEmail, '+').'@'.$aliasDomain;
    }

    // Check if the alias already exists or not
    $noAliasExists = Database::table('aliases')->select('id')->where('email', $aliasEmail)->doesntExist();

    if ($noAliasExists && $aliasHasSharedDomain) {
        // If admin username is set then allow through with catch-all
        if ($adminUsername) {
            sendAction('DUNNO');
        } else {
            sendAction(ACTION_DOES_NOT_EXIST);
        }
    } else {
        $aliasAction = null;

        if (! $noAliasExists) {
            $aliasActionQuery = Database::table('aliases')
                ->leftJoin('users', 'aliases.user_id', '=', 'users.id')
                ->where('aliases.email', $aliasEmail)
                ->selectRaw('CASE
                WHEN aliases.deleted_at IS NOT NULL THEN ?
                WHEN aliases.active = 0 THEN ?
                WHEN users.reject_until > NOW() THEN ?
                WHEN users.defer_until > NOW() THEN ?
                ELSE "DUNNO"
                END', [
                    ACTION_DOES_NOT_EXIST,
                    ACTION_ALIAS_DISCARD,
                    ACTION_REJECT,
                    ACTION_DEFER,
                ])
                ->first();

            $aliasAction = getAction($aliasActionQuery);
        }

        if (in_array($aliasAction, [ACTION_ALIAS_DISCARD, ACTION_DOES_NOT_EXIST])) {
            // If the alias is inactive or deleted then increment the blocked count
            Database::table('aliases')
                ->where('email', $aliasEmail)
                ->increment('emails_blocked', 1, ['last_blocked' => new DateTime()]);

            sendAction($aliasAction);
        } elseif ($aliasHasSharedDomain || in_array($aliasAction, [ACTION_REJECT, ACTION_DEFER])) {
            // If the alias has a shared domain then we don't need to check the usernames or domains

            sendAction($aliasAction);
        } elseif ($aliasHasUsernameDomain) {
            $concatDomainsStatement = array_reduce(array_keys($allDomains), function ($carry, $key) {
                $comma = $key === 0 ? '' : ',';

                return "{$carry}{$comma}CONCAT(usernames.username, ?)";
            }, '');

            $dotDomains = array_map(fn ($domain) => ".{$domain}", $allDomains);

            $usernameActionQuery = Database::table('usernames')
                ->leftJoin('users', 'usernames.user_id', '=', 'users.id')
                ->whereRaw('? IN ('.$concatDomainsStatement.')', [$aliasDomain, ...$dotDomains])
                ->selectRaw('CASE
                WHEN ? AND usernames.catch_all = 0 THEN ?
                WHEN usernames.active = 0 THEN ?
                WHEN users.reject_until > NOW() THEN ?
                WHEN users.defer_until > NOW() THEN ?
                WHEN ? AND users.defer_new_aliases_until > NOW() THEN ?
                ELSE "DUNNO"
                END', [
                    $noAliasExists,
                    ACTION_DOES_NOT_EXIST,
                    ACTION_USERNAME_DISCARD,
                    ACTION_REJECT,
                    ACTION_DEFER,
                    $noAliasExists,
                    ACTION_DEFER_NEW,
                ])
                ->first();

            sendAction(getAction($usernameActionQuery));
        } else {
            $domainActionQuery = Database::table('domains')
                ->leftJoin('users', 'domains.user_id', '=', 'users.id')
                ->where('domains.domain', $aliasDomain)
                ->selectRaw('CASE
                WHEN ? AND domains.catch_all = 0 THEN ?
                WHEN domains.active = 0 THEN ?
                WHEN users.reject_until > NOW() THEN ?
                WHEN users.defer_until > NOW() THEN ?
                WHEN ? AND users.defer_new_aliases_until > NOW() THEN ?
                ELSE "DUNNO"
                END', [
                    $noAliasExists,
                    ACTION_DOES_NOT_EXIST,
                    ACTION_DOMAIN_DISCARD,
                    ACTION_REJECT,
                    ACTION_DEFER,
                    $noAliasExists,
                    ACTION_DEFER_NEW,
                ])
                ->first();

            sendAction(getAction($domainActionQuery));
        }
    }
} catch (\Exception $e) {
    logData($e->getMessage());

    exit(0);
}

// Get the arguments sent by Postfix
function getArgs()
{
    $args = [];
    while ($line = trim(fgets(STDIN))) {
        [$key, $value] = explode('=', $line, 2);
        $args[$key] = $value;
    }

    return $args;
}

// Get the action from the action query result
function getAction($actionQuery)
{
    return is_object($actionQuery) ? array_values(get_object_vars($actionQuery))[0] : null;
}

// Send the action back to Postfix
function sendAction($action)
{
    echo 'action='.$action."\n\n";
}

// Get the outbound message ID from the VERP address
function getIdFromVerp($verpLocalPart, $verpEmail)
{
    $parts = explode('_', $verpLocalPart);

    if (count($parts) !== 3) {
        //logData('VERP invalid email: '.$verp);

        return;
    }

    try {
        $id = Base32::decodeNoPadding($parts[1]);

        $signature = Base32::decodeNoPadding($parts[2]);
    } catch (\Exception $e) {
        logData('VERP base32 decode failure: '.$verpEmail.' '.$e->getMessage());

        return;
    }

    $expectedSignature = substr(hash_hmac('sha3-224', $id, $_ENV['ANONADDY_VERP_SECRET'] ?? ''), 0, 8);

    if ($signature !== $expectedSignature) {
        logData('VERP invalid signature: '.$verpEmail);

        return;
    }

    return $id;
}

// Get the portion of a string before the first occurrence of a given value
function before($subject, $search)
{
    if ($search === '') {
        return $subject;
    }

    $result = strstr($subject, (string) $search, true);

    return $result === false ? $subject : $result;
}

// Get the portion of a string before the last occurrence of a given value.
function beforeLast($subject, $search)
{
    if ($search === '') {
        return $subject;
    }

    $pos = mb_strrpos($subject, $search);

    if ($pos === false) {
        return $subject;
    }

    return mb_substr($subject, 0, $pos, 'UTF-8');
}

// Determine if a given string ends with a given substring
function endsWith($haystack, $needles)
{
    if (! is_iterable($needles)) {
        $needles = (array) $needles;
    }

    foreach ($needles as $needle) {
        if ((string) $needle !== '' && str_ends_with($haystack, $needle)) {
            return true;
        }
    }

    return false;
}

function logData($data)
{
    file_put_contents(__DIR__.'/../storage/logs/postfix-access-policy.log', '['.(new DateTime())->format('Y-m-d H:i:s').'] '.$data.PHP_EOL, FILE_APPEND);
}
