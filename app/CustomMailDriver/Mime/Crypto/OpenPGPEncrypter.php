<?php

namespace App\CustomMailDriver\Mime\Crypto;

use App\CustomMailDriver\Mime\Part\EncryptedPart;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\RuntimeException;
use Symfony\Component\Mime\Email;

class OpenPGPEncrypter
{
    protected $gnupg = null;

    protected $usesProtectedHeaders;

    /**
     * The signing hash algorithm. 'MD5', SHA1, or SHA256. SHA256 (the default) is highly recommended
     * unless you need to deal with an old client that doesn't support it. SHA1 and MD5 are
     * currently considered cryptographically weak.
     *
     * This is apparently not supported by the PHP GnuPG module.
     *
     * @type string
     */
    protected $micalg = 'SHA256';

    protected $recipientKey = null;

    /**
     * The fingerprint of the key that will be used to sign the email. Populated either with
     * autoAddSignature or addSignature.
     *
     * @type string
     */
    protected $signingKey;

    /**
     * An associative array of keyFingerprint=>passwords to decrypt secret keys (if needed).
     * Populated by calling addKeyPassphrase. Pointless at the moment because the GnuPG module in
     * PHP doesn't support decrypting keys with passwords. The command line client does, so this
     * method stays for now.
     *
     * @type array
     */
    protected $keyPassphrases = [];

    /**
     * Specifies the home directory for the GnuPG keyrings. By default this is the user's home
     * directory + /.gnupg, however when running on a web server (eg: Apache) the home directory
     * will likely not exist and/or not be writable. Set this by calling setGPGHome before calling
     * any other encryption/signing methods.
     *
     * @var string
     */
    protected $gnupgHome = null;

    public function __construct($signingKey = null, $recipientKey = null, $gnupgHome = null, $usesProtectedHeaders = false)
    {
        $this->initGNUPG();
        $this->signingKey = $signingKey;
        $this->recipientKey = $recipientKey;
        $this->gnupgHome = $gnupgHome;
        $this->usesProtectedHeaders = $usesProtectedHeaders;
    }

    /**
     * @param  string  $micalg
     */
    public function setMicalg($micalg)
    {
        $this->micalg = $micalg;
    }

    /**
     * @param  null  $passPhrase
     *
     * @throws RuntimeException
     */
    public function addSignature($identifier, $keyFingerprint = null, $passPhrase = null)
    {
        if (! $keyFingerprint) {
            $keyFingerprint = $this->getKey($identifier, 'sign');
        }
        $this->signingKey = $keyFingerprint;

        if ($passPhrase) {
            $this->addKeyPassphrase($keyFingerprint, $passPhrase);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function addKeyPassphrase($identifier, $passPhrase)
    {
        $keyFingerprint = $this->getKey($identifier, 'sign');
        $this->keyPassphrases[$keyFingerprint] = $passPhrase;
    }

    /**
     * @param  Email  $email
     * @return $this
     *
     * @throws RuntimeException
     */
    public function encrypt(Email $message): Email
    {
        $originalMessage = clone $message;

        $headers = $message->getPreparedHeaders();

        $boundary = strtr(base64_encode(random_bytes(6)), '+/', '-_');

        $headers->setHeaderBody('Parameterized', 'Content-Type', 'multipart/encrypted');
        $headers->setHeaderParameter('Content-Type', 'protocol', 'application/pgp-encrypted');
        $headers->setHeaderParameter('Content-Type', 'boundary', $boundary);

        $message->setHeaders($headers);

        // If the email does not have any text part then we need to add a text/plain legacy display part
        if ($this->usesProtectedHeaders && is_null($originalMessage->getTextBody())) {
            $originalMessage->text($headers->get('Subject')->toString());
        }

        $lines = preg_split('/(\r\n|\r|\n)/', rtrim($originalMessage->toString()));

        // Check if using protected headers or not
        if ($this->usesProtectedHeaders) {
            $protectedHeadersSet = false;
            for ($i = 0; $i < count($lines); $i++) {
                if (Str::startsWith(strtolower($lines[$i]), 'content-type: text/plain') || Str::startsWith(strtolower($lines[$i]), 'content-type: multipart/')) {
                    $lines[$i] = rtrim($lines[$i])."; protected-headers=\"v1\"\r\n";
                    if (! $protectedHeadersSet) {
                        $headers->setHeaderBody('Text', 'Subject', '...');
                        $protectedHeadersSet = true;
                    }
                } else {
                    $lines[$i] = rtrim($lines[$i])."\r\n";
                }
            }
        } else {
            for ($i = 0; $i < count($lines); $i++) {
                $lines[$i] = rtrim($lines[$i])."\r\n";
            }
        }

        // Remove excess trailing newlines (RFC3156 section 5.4)
        $originalBody = rtrim(implode('', $lines))."\r\n";

        // Create encrypted body from original message
        $encryptedBody = $this->pgpEncryptAndSignString($originalBody, $this->recipientKey, $this->signingKey);

        // Fixes DKIM signature incorrect body hash for custom domains
        $body = "This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/pgp-encrypted\r\n";
        $body .= "Content-Description: PGP/MIME version identification\r\n\r\n";
        $body .= "Version: 1\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/octet-stream; name=\"encrypted.asc\"\r\n";
        $body .= "Content-Description: OpenPGP encrypted message\r\n";
        $body .= "Content-Disposition: inline; filename=\"encrypted.asc\"\r\n\r\n";
        $body .= $encryptedBody."\r\n\r\n";
        $body .= "--{$boundary}--";

        return $message->setBody(new EncryptedPart($body));
    }

    /**
     * @param  Email  $email
     * @return $this
     *
     * @throws RuntimeException
     */
    public function encryptInline(Email $message): Email
    {
        if (! $this->signingKey) {
            foreach ($message->getFrom() as $key => $value) {
                $this->addSignature($this->getKey($key, 'sign'));
            }
        }

        if (! $this->signingKey) {
            throw new RuntimeException('Signing has been enabled, but no signature has been added. Use autoAddSignature() or addSignature()');
        }

        if (! $this->recipientKey) {
            throw new RuntimeException('Encryption has been enabled, but no recipients have been added. Use autoAddRecipients() or addRecipient()');
        }

        $body = $message->getTextBody() ?? '';

        $text = $this->pgpEncryptAndSignString($body, $this->recipientKey, $this->signingKey);

        $headers = $message->getPreparedHeaders();
        $headers->setHeaderBody('Parameterized', 'Content-Type', 'text/plain');
        $headers->setHeaderParameter('Content-Type', 'charset', 'utf-8');
        $message->setHeaders($headers);

        return $message->setBody(new EncryptedPart($text));
    }

    /**
     * @throws RuntimeException
     */
    protected function initGNUPG()
    {
        if (! class_exists('gnupg')) {
            throw new RuntimeException('PHPMailerPGP requires the GnuPG class');
        }

        if (! $this->gnupgHome && isset($_SERVER['HOME'])) {
            $this->gnupgHome = $_SERVER['HOME'].'/.gnupg';
        }

        if (! $this->gnupgHome && getenv('HOME')) {
            $this->gnupgHome = getenv('HOME').'/.gnupg';
        }

        if (! $this->gnupg) {
            $this->gnupg = new \gnupg();
        }

        $this->gnupg->seterrormode(\gnupg::ERROR_EXCEPTION);
    }

    /**
     * @param $plaintext
     * @param $keyFingerprints
     * @return string
     *
     * @throws RuntimeException
     */
    protected function pgpEncryptAndSignString($text, $keyFingerprint, $signingKeyFingerprint)
    {
        if (isset($this->keyPassphrases[$signingKeyFingerprint]) && ! $this->keyPassphrases[$signingKeyFingerprint]) {
            $passPhrase = $this->keyPassphrases[$signingKeyFingerprint];
        } else {
            $passPhrase = null;
        }

        $this->gnupg->clearsignkeys();
        $this->gnupg->addsignkey($signingKeyFingerprint, $passPhrase);
        $this->gnupg->clearencryptkeys();
        $this->gnupg->addencryptkey($keyFingerprint);
        $this->gnupg->setarmor(1);

        $encrypted = $this->gnupg->encryptsign($text);

        if ($encrypted) {
            return $encrypted;
        }

        throw new RuntimeException('Unable to encrypt and sign message');
    }

    /**
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getKey($identifier, $purpose)
    {
        $keys = $this->gnupg->keyinfo($identifier);
        $fingerprints = [];

        foreach ($keys as $key) {
            if ($this->isValidKey($key, $purpose)) {
                foreach ($key['subkeys'] as $subKey) {
                    if ($this->isValidKey($subKey, $purpose)) {
                        $fingerprints[] = $subKey['fingerprint'];
                    }
                }
            }
        }

        // Return first available to encrypt
        if (count($fingerprints) >= 1) {
            return $fingerprints[0];
        }

        /* if (count($fingerprints) > 1) {
            throw new Swift_SwiftException(sprintf('Found more than one active key for %s use addRecipient() or addSignature()', $identifier));
        } */

        throw new RuntimeException(sprintf('Unable to find an active key to %s for %s,try importing keys first', $purpose, $identifier));
    }

    protected function isValidKey($key, $purpose)
    {
        return ! ($key['disabled'] || $key['expired'] || $key['revoked'] || ($purpose == 'sign' && ! $key['can_sign']) || ($purpose == 'encrypt' && ! $key['can_encrypt']));
    }
}
