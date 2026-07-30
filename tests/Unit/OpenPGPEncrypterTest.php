<?php

namespace Tests\Unit;

use App\CustomMailDriver\Mime\Crypto\OpenPGPEncrypter;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class OpenPGPEncrypterTest extends TestCase
{
    #[Test]
    public function it_parses_encryption_subkey_fingerprints_from_colon_output(): void
    {
        $encrypter = (new ReflectionClass(OpenPGPEncrypter::class))->newInstanceWithoutConstructor();

        $parseColonKeyFingerprints = new \ReflectionMethod(OpenPGPEncrypter::class, 'parseColonKeyFingerprints');
        $parseColonKeyFingerprints->setAccessible(true);

        $colonOutput = <<<'OUTPUT'
pub:u:255:22:BFC9F7CC63D7EA05:1780666572:::u:::cEC:::::ed25519:::0:
fpr:::::::::C62AABA4A78FC1719B9DBE2EBFC9F7CC63D7EA05:
uid:u::::1780666572::8CA06817FA2B13D01F6E07B37B27F516978A9045::Parse Test <p@test.local>::::::::::0:
sub:u:255:18:42D1D75A88B56102:1780666574::::::e:::::cv25519::
fpr:::::::::A90DD4079059FC4D842FEE1242D1D75A88B56102:
sub:u:255:18:4A9EDD8212556ED2:1780666575::::::e:::::cv25519::
fpr:::::::::1C3176DBBAE9D0F5C7F1A08D4A9EDD8212556ED2:
sub:r:255:18:DEADBEEFDEADBEEF:1780666576::::::e:::::cv25519::
fpr:::::::::AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA:
OUTPUT;

        $fingerprints = $parseColonKeyFingerprints->invoke($encrypter, $colonOutput, 'encrypt');

        $this->assertSame([
            'A90DD4079059FC4D842FEE1242D1D75A88B56102',
            '1C3176DBBAE9D0F5C7F1A08D4A9EDD8212556ED2',
        ], $fingerprints);
    }

    #[Test]
    public function it_throws_when_tilde_gnupg_home_cannot_be_resolved_without_home(): void
    {
        if (! function_exists('posix_getpwuid')) {
            $this->markTestSkipped('posix extension is not available');
        }

        $encrypter = (new ReflectionClass(OpenPGPEncrypter::class))->newInstanceWithoutConstructor();

        $resolveGnupgHome = new \ReflectionMethod(OpenPGPEncrypter::class, 'resolveGnupgHome');
        $resolveGnupgHome->setAccessible(true);

        $gnupgHome = new \ReflectionProperty(OpenPGPEncrypter::class, 'gnupgHome');
        $gnupgHome->setAccessible(true);
        $gnupgHome->setValue($encrypter, '~/.gnupg');

        $originalHome = getenv('HOME');
        $originalServerHome = $_SERVER['HOME'] ?? null;
        putenv('HOME');
        unset($_SERVER['HOME']);
        config(['anonaddy.gnupg_home' => null]);

        try {
            $resolved = $resolveGnupgHome->invoke($encrypter);

            $this->assertNotSame('/.gnupg', $resolved);
            $this->assertStringEndsWith('/.gnupg', $resolved);
        } finally {
            if ($originalHome !== false) {
                putenv('HOME='.$originalHome);
            }
            if ($originalServerHome !== null) {
                $_SERVER['HOME'] = $originalServerHome;
            }
        }
    }

    #[Test]
    public function it_throws_when_resolved_gnupg_home_is_invalid(): void
    {
        $encrypter = (new ReflectionClass(OpenPGPEncrypter::class))->newInstanceWithoutConstructor();

        $guardResolvableGnupgHome = new \ReflectionMethod(OpenPGPEncrypter::class, 'guardResolvableGnupgHome');
        $guardResolvableGnupgHome->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid path');

        $guardResolvableGnupgHome->invoke($encrypter, '/.gnupg');
    }

    #[Test]
    public function it_reads_encryption_subkeys_from_the_configured_gnupg_home(): void
    {
        if (! $this->gpgIsAvailable()) {
            $this->markTestSkipped('gpg is not available');
        }

        $gnupgHome = sys_get_temp_dir().'/gpg-openpgp-encrypter-test-'.uniqid();
        mkdir($gnupgHome, 0700, true);
        $originalGnupgHome = getenv('GNUPGHOME');

        try {
            $generate = Process::timeout(30)
                ->env(['GNUPGHOME' => $gnupgHome])
                ->run([
                    'gpg', '--homedir', $gnupgHome, '--batch', '--pinentry-mode', 'loopback', '--passphrase', 'testpass',
                    '--quick-generate-key', 'OpenPGP Encrypter Test <encrypter@test.local>', 'ed25519', 'cert', '0',
                ]);

            $this->assertTrue($generate->successful(), $generate->errorOutput());

            $fingerprint = trim(Process::timeout(30)
                ->env(['GNUPGHOME' => $gnupgHome])
                ->run(['gpg', '--homedir', $gnupgHome, '--list-keys', '--with-colons', 'encrypter@test.local'])
                ->output());

            preg_match('/^fpr:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:([A-F0-9]+):/m', $fingerprint, $matches);
            $primaryFingerprint = $matches[1] ?? null;
            $this->assertNotNull($primaryFingerprint);

            foreach (range(1, 2) as $index) {
                $addKey = Process::timeout(30)
                    ->env(['GNUPGHOME' => $gnupgHome])
                    ->run([
                        'gpg', '--homedir', $gnupgHome, '--batch', '--pinentry-mode', 'loopback', '--passphrase', 'testpass',
                        '--quick-add-key', $primaryFingerprint, 'cv25519', 'encrypt', '0',
                    ]);

                $this->assertTrue($addKey->successful(), $addKey->errorOutput());
            }

            $export = Process::timeout(30)
                ->env(['GNUPGHOME' => $gnupgHome])
                ->run(['gpg', '--homedir', $gnupgHome, '--armor', '--export', $primaryFingerprint]);

            $this->assertTrue($export->successful(), $export->errorOutput());

            config(['anonaddy.gnupg_home' => $gnupgHome]);

            $encrypter = new OpenPGPEncrypter(null, $primaryFingerprint, $gnupgHome, false);

            $getSubkeyFingerprints = new \ReflectionMethod(OpenPGPEncrypter::class, 'getSubkeyFingerprints');
            $getSubkeyFingerprints->setAccessible(true);

            $fingerprints = $getSubkeyFingerprints->invoke($encrypter, $primaryFingerprint, 'encrypt');

            $this->assertCount(2, $fingerprints);
        } finally {
            $this->deleteDirectory($gnupgHome);

            if ($originalGnupgHome === false) {
                putenv('GNUPGHOME');
            } else {
                putenv('GNUPGHOME='.$originalGnupgHome);
            }
        }
    }

    #[Test]
    public function it_encrypts_large_plaintext_without_deadlocking(): void
    {
        if (! $this->gpgIsAvailable()) {
            $this->markTestSkipped('gpg is not available');
        }

        if (! extension_loaded('gnupg')) {
            $this->markTestSkipped('gnupg extension is not available');
        }

        $gnupgHome = sys_get_temp_dir().'/gpg-openpgp-encrypter-large-test-'.uniqid();
        mkdir($gnupgHome, 0700, true);
        $originalGnupgHome = getenv('GNUPGHOME');

        try {
            [$primaryFingerprint, $encryptFingerprint] = $this->createEncryptionKeyPair($gnupgHome);

            config(['anonaddy.gnupg_home' => $gnupgHome]);

            $encrypter = new OpenPGPEncrypter(null, $primaryFingerprint, $gnupgHome, false);

            $runGpgCommand = new \ReflectionMethod(OpenPGPEncrypter::class, 'runGpgCommand');
            $runGpgCommand->setAccessible(true);

            $largeInput = str_repeat('A', 700 * 1024);

            $start = microtime(true);

            $encrypted = $runGpgCommand->invoke($encrypter, [
                '--encrypt',
                '-r', $encryptFingerprint.'!',
            ], $largeInput);

            $duration = microtime(true) - $start;

            $this->assertLessThan(30, $duration);
            $this->assertStringContainsString('BEGIN PGP MESSAGE', $encrypted);
        } finally {
            $this->deleteDirectory($gnupgHome);

            if ($originalGnupgHome === false) {
                putenv('GNUPGHOME');
            } else {
                putenv('GNUPGHOME='.$originalGnupgHome);
            }
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function createEncryptionKeyPair(string $gnupgHome): array
    {
        $generate = Process::timeout(30)
            ->env(['GNUPGHOME' => $gnupgHome])
            ->run([
                'gpg', '--homedir', $gnupgHome, '--batch', '--pinentry-mode', 'loopback', '--passphrase', 'testpass',
                '--quick-generate-key', 'Large Encrypt Test <large-encrypt@test.local>', 'ed25519', 'cert', '0',
            ]);

        $this->assertTrue($generate->successful(), $generate->errorOutput());

        $listKeys = Process::timeout(30)
            ->env(['GNUPGHOME' => $gnupgHome])
            ->run(['gpg', '--homedir', $gnupgHome, '--list-keys', '--with-colons', 'large-encrypt@test.local']);

        $this->assertTrue($listKeys->successful(), $listKeys->errorOutput());

        preg_match('/^fpr:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:([A-F0-9]+):/m', $listKeys->output(), $matches);
        $primaryFingerprint = $matches[1] ?? null;
        $this->assertNotNull($primaryFingerprint);

        $addKey = Process::timeout(30)
            ->env(['GNUPGHOME' => $gnupgHome])
            ->run([
                'gpg', '--homedir', $gnupgHome, '--batch', '--pinentry-mode', 'loopback', '--passphrase', 'testpass',
                '--quick-add-key', $primaryFingerprint, 'cv25519', 'encrypt', '0',
            ]);

        $this->assertTrue($addKey->successful(), $addKey->errorOutput());

        $listSubkeys = Process::timeout(30)
            ->env(['GNUPGHOME' => $gnupgHome])
            ->run(['gpg', '--homedir', $gnupgHome, '--list-keys', '--with-colons', 'large-encrypt@test.local']);

        preg_match_all('/^fpr:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:[^:]*:([A-F0-9]+):/m', $listSubkeys->output(), $fingerprintMatches);

        $encryptFingerprint = $fingerprintMatches[1][1] ?? null;
        $this->assertNotNull($encryptFingerprint);

        return [$primaryFingerprint, $encryptFingerprint];
    }

    protected function gpgIsAvailable(): bool
    {
        $result = Process::timeout(5)->run(['gpg', '--version']);

        return $result->successful();
    }

    protected function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        // Kill any agent tied to this temp home so sockets/lock files are not
        // removed out from under us mid-walk (common on GitHub CI).
        Process::timeout(5)
            ->env(['GNUPGHOME' => $directory])
            ->run(['gpgconf', '--kill', 'gpg-agent']);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $path = $file->getPathname();

            if ($file->isDir()) {
                @rmdir($path);

                continue;
            }

            // getRealPath() is false for vanished sockets / broken links; those
            // are common under GNUPGHOME when gpg-agent cleans up asynchronously.
            if (file_exists($path) || is_link($path)) {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
