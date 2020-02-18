<?php

namespace App\Helpers;

use Swift_DependencyContainer;
use Swift_Message;
use Swift_Signers_BodySigner;
use Swift_SwiftException;

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Message Signer used to apply OpenPGP Signature/Encryption to a message.
 *
 * @author Artem Zhuravlev <infzanoza@gmail.com>
 */
class OpenPGPSigner implements Swift_Signers_BodySigner
{
    protected $gnupg = null;

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

    /**
     * An associative array of identifier=>keyFingerprint for the recipients we'll encrypt the email
     * to, where identifier is usually the email address, but could be anything used to look up a
     * key (including the fingerprint itself). This is populated either by autoAddRecipients or by
     * calling addRecipient.
     *
     * @type array
     */
    protected $recipientKeys = [];

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

    /**
     * @var bool
     */
    protected $encrypt = true;


    public function __construct($signingKey = null, $recipientKeys = [], $gnupgHome = null)
    {
        $this->initGNUPG();
        $this->signingKey    = $signingKey;
        $this->recipientKeys = $recipientKeys;
        $this->gnupgHome     = $gnupgHome;
    }

    public static function newInstance($signingKey = null, $recipientKeys = [], $gnupgHome = null)
    {
        return new self($signingKey, $recipientKeys, $gnupgHome);
    }

    /**
     * @param boolean $encrypt
     */
    public function setEncrypt($encrypt)
    {
        $this->encrypt = $encrypt;
    }

    /**
     * @param string $gnupgHome
     */
    public function setGnupgHome($gnupgHome)
    {
        $this->gnupgHome = $gnupgHome;
    }

    /**
     * @param string $micalg
     */
    public function setMicalg($micalg)
    {
        $this->micalg = $micalg;
    }

    /**
     * @param $identifier
     * @param null $passPhrase
     *
     * @throws Swift_SwiftException
     */
    public function addSignature($identifier, $keyFingerprint = null, $passPhrase = null)
    {
        if (!$keyFingerprint) {
            $keyFingerprint   = $this->getKey($identifier, 'sign');
        }
        $this->signingKey = $keyFingerprint;

        if ($passPhrase) {
            $this->addKeyPassphrase($keyFingerprint, $passPhrase);
        }
    }

    /**
     * @param $identifier
     * @param $passPhrase
     *
     * @throws Swift_SwiftException
     */
    public function addKeyPassphrase($identifier, $passPhrase)
    {
        $keyFingerprint                        = $this->getKey($identifier, 'sign');
        $this->keyPassphrases[$keyFingerprint] = $passPhrase;
    }

    /**
     * Adds a recipient to encrypt a copy of the email for. If you exclude a key fingerprint, we
     * will try to find a matching key based on the identifier. However if no match is found, or
     * if multiple valid keys are found, this will fail. Specifying a key fingerprint avoids these
     * issues.
     *
     * @param string $identifier
     * an email address, but could be a key fingerprint, key ID, name, etc.
     *
     * @param string $keyFingerprint
     */
    public function addRecipient($identifier, $keyFingerprint = null)
    {
        if (!$keyFingerprint) {
            $keyFingerprint = $this->getKey($identifier, 'encrypt');
        }

        $this->recipientKeys[$identifier] = $keyFingerprint;
    }

    /**
     * @param Swift_Message $message
     *
     * @return $this
     *
     * @throws Swift_DependencyException
     * @throws Swift_SwiftException
     */
    public function signMessage(Swift_Message $message)
    {
        $originalMessage = $this->createMessage($message);

        $message->setChildren([]);

        $message->setEncoder(Swift_DependencyContainer::getInstance()->lookup('mime.rawcontentencoder'));

        $type = $message->getHeaders()->get('Content-Type');

        $type->setValue('multipart/signed');

        $type->setParameters([
            'micalg'   => sprintf("pgp-%s", strtolower($this->micalg)),
            'protocol' => 'application/pgp-signature',
            'boundary' => $message->getBoundary()
        ]);

        if (!$this->signingKey) {
            foreach ($message->getFrom() as $key => $value) {
                $this->addSignature($this->getKey($key, 'sign'));
            }
        }

        if (!$this->signingKey) {
            throw new Swift_SwiftException('Signing has been enabled, but no signature has been added. Use autoAddSignature() or addSignature()');
        }

        $signedBody = $originalMessage->toString();

        $lines = preg_split('/(\r\n|\r|\n)/', rtrim($signedBody));

        for ($i=0; $i<count($lines); $i++) {
            $lines[$i] = rtrim($lines[$i])."\r\n";
        }

        // Remove excess trailing newlines (RFC3156 section 5.4)
        $signedBody = rtrim(implode('', $lines))."\r\n";

        $signature = $this->pgpSignString($signedBody, $this->signingKey);

        //Swiftmailer is automatically changing content type and this is the hack to prevent it
        // Fixes DKIM signature incorrect body hash for custom domains
        $body = "This is an OpenPGP/MIME signed message (RFC 4880 and 3156)\r\n\r\n";
        $body .= "--{$message->getBoundary()}\r\n";
        $body .= $signedBody."\r\n";
        $body .= "--{$message->getBoundary()}\r\n";
        $body .= "Content-Type: application/pgp-signature; name=\"signature.asc\"\r\n";
        $body .= "Content-Description: OpenPGP digital signature\r\n";
        $body .= "Content-Disposition: attachment; filename=\"signature.asc\"\r\n\r\n";
        $body .= $signature."\r\n\r\n";
        $body .= "--{$message->getBoundary()}--";

        $message->setBody($body);

        if ($this->encrypt) {
            $signed = sprintf("%s\r\n%s", $message->getHeaders()->get('Content-Type')->toString(), $body);

            if (!$this->recipientKeys) {
                foreach ($message->getTo() as $key => $value) {
                    if (!isset($this->recipientKeys[$key])) {
                        $this->addRecipient($key);
                    }
                }
            }

            if (!$this->recipientKeys) {
                throw new Swift_SwiftException('Encryption has been enabled, but no recipients have been added. Use autoAddRecipients() or addRecipient()');
            }

            //Create body from signed message
            $encryptedBody = $this->pgpEncryptString($signed, array_keys($this->recipientKeys));

            $type = $message->getHeaders()->get('Content-Type');

            $type->setValue('multipart/encrypted');

            $type->setParameters([
                'protocol' => 'application/pgp-encrypted',
                'boundary' => $message->getBoundary()
            ]);

            // Fixes DKIM signature incorrect body hash for custom domains
            $body = "This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)\r\n\r\n";
            $body .= "--{$message->getBoundary()}\r\n";
            $body .= "Content-Type: application/pgp-encrypted\r\n";
            $body .= "Content-Description: PGP/MIME version identification\r\n\r\n";
            $body .= "Version: 1\r\n\r\n";
            $body .= "--{$message->getBoundary()}\r\n";
            $body .= "Content-Type: application/octet-stream; name=\"encrypted.asc\"\r\n";
            $body .= "Content-Description: OpenPGP encrypted message\r\n";
            $body .= "Content-Disposition: inline; filename=\"encrypted.asc\"\r\n\r\n";
            $body .= $encryptedBody."\r\n\r\n";
            $body .= "--{$message->getBoundary()}--";

            $message->setBody($body);
        }

        $messageHeaders = $message->getHeaders();
        $messageHeaders->removeAll('Content-Transfer-Encoding');

        return $this;
    }

    /**
     * @return array
     */
    public function getAlteredHeaders()
    {
        return ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition', 'Content-Description'];
    }

    /**
     * @return $this
     */
    public function reset()
    {
        return $this;
    }

    protected function createMessage(Swift_Message $message)
    {
        $mimeEntity = new Swift_Message('', $message->getBody(), $message->getContentType(), $message->getCharset());
        $mimeEntity->setChildren($message->getChildren());

        $messageHeaders = $mimeEntity->getHeaders();
        $messageHeaders->remove('Message-ID');
        $messageHeaders->remove('Date');
        $messageHeaders->remove('Subject');
        $messageHeaders->remove('MIME-Version');
        $messageHeaders->remove('To');
        $messageHeaders->remove('From');

        return $mimeEntity;
    }

    /**
     * @throws Swift_SwiftException
     */
    protected function initGNUPG()
    {
        if (!class_exists('gnupg')) {
            throw new Swift_SwiftException('PHPMailerPGP requires the GnuPG class');
        }

        if (!$this->gnupgHome && isset($_SERVER['HOME'])) {
            $this->gnupgHome = $_SERVER['HOME'] . '/.gnupg';
        }

        if (!$this->gnupgHome && getenv('HOME')) {
            $this->gnupgHome = getenv('HOME') . '/.gnupg';
        }

        if (!$this->gnupg) {
            $this->gnupg = new \gnupg();
        }

        $this->gnupg->seterrormode(\gnupg::ERROR_EXCEPTION);
    }

    /**
     * @param $plaintext
     * @param $keyFingerprint
     *
     * @return string
     *
     * @throws Swift_SwiftException
     */
    protected function pgpSignString($plaintext, $keyFingerprint)
    {
        if (isset($this->keyPassphrases[$keyFingerprint]) && !$this->keyPassphrases[$keyFingerprint]) {
            $passPhrase = $this->keyPassphrases[$keyFingerprint];
        } else {
            $passPhrase = null;
        }

        $this->gnupg->clearsignkeys();
        $this->gnupg->addsignkey($keyFingerprint, $passPhrase);
        $this->gnupg->setsignmode(\gnupg::SIG_MODE_DETACH);
        $this->gnupg->setarmor(1);

        $signed = $this->gnupg->sign($plaintext);

        if ($signed) {
            return $signed;
        }

        throw new Swift_SwiftException('Unable to sign message (perhaps the secret key is encrypted with a passphrase?)');
    }

    /**
     * @param $plaintext
     * @param $keyFingerprints
     *
     * @return string
     *
     * @throws Swift_SwiftException
     */
    protected function pgpEncryptString($plaintext, $keyFingerprints)
    {
        $this->gnupg->clearencryptkeys();

        foreach ($keyFingerprints as $keyFingerprint) {
            $this->gnupg->addencryptkey($keyFingerprint);
        }

        $this->gnupg->setarmor(1);

        $encrypted = $this->gnupg->encrypt($plaintext);

        if ($encrypted) {
            return $encrypted;
        }

        throw new Swift_SwiftException('Unable to encrypt message');
    }

    /**
     * @param $identifier
     * @param $purpose
     *
     * @return string
     *
     * @throws Swift_SwiftException
     */
    protected function getKey($identifier, $purpose)
    {
        $keys         = $this->gnupg->keyinfo($identifier);
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

        throw new Swift_SwiftException(sprintf('Unable to find an active key to %s for %s,try importing keys first', $purpose, $identifier));
    }

    protected function isValidKey($key, $purpose)
    {
        return !($key['disabled'] || $key['expired'] || $key['revoked'] || ($purpose == 'sign' && !$key['can_sign']) || ($purpose == 'encrypt' && !$key['can_encrypt']));
    }
}
