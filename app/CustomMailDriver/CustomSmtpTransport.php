<?php

namespace App\CustomMailDriver;

use App\Models\PostfixQueueId;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Swift_AddressEncoderException;
use Swift_DependencyContainer;
use Swift_Events_SendEvent;
use Swift_Mime_SimpleMessage;
use Swift_Plugins_LoggerPlugin;
use Swift_Plugins_Loggers_ArrayLogger;
use Swift_Transport_EsmtpTransport;
use Swift_TransportException;

class CustomSmtpTransport extends Swift_Transport_EsmtpTransport
{
    /**
     * @param string $host
     * @param int    $port
     * @param string|null $encryption SMTP encryption mode:
     *        - null for plain SMTP (no encryption),
     *        - 'tls' for SMTP with STARTTLS (best effort encryption),
     *        - 'ssl' for SMTPS = SMTP over TLS (always encrypted).
     */
    public function __construct($host = 'localhost', $port = 25, $encryption = null)
    {
        \call_user_func_array(
            [$this, 'Swift_Transport_EsmtpTransport::__construct'],
            Swift_DependencyContainer::getInstance()
                ->createDependenciesFor('transport.smtp')
        );

        $this->setHost($host);
        $this->setPort($port);
        $this->setEncryption($encryption);
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * @param string[] $failedRecipients An array of failures by-reference
     *
     * @return int
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        $logger = new Swift_Plugins_Loggers_ArrayLogger();
        Mail::getSwiftMailer()->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));

        $sent = 0;
        $failedRecipients = (array) $failedRecipients;

        if ($evt = $this->eventDispatcher->createSendEvent($this, $message)) {
            $this->eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        if (!$reversePath = $this->getReversePath($message)) {
            $this->throwException(new Swift_TransportException('Cannot send message without a sender address'));
        }

        $to = (array) $message->getTo();
        $cc = (array) $message->getCc();
        $tos = array_merge($to, $cc);
        $bcc = (array) $message->getBcc();

        $message->setBcc([]);

        // This allows us to have the To: header set as the alias whilst still delivering to the correct RCPT TO.
        if ($aliasTo = $message->getHeaders()->get('Alias-To')) {
            $message->setTo($aliasTo->getFieldBodyModel());
            $message->getHeaders()->remove('Alias-To');
        }

        try {
            $sent += $this->sendTo($message, $reversePath, $tos, $failedRecipients);
            $sent += $this->sendBcc($message, $reversePath, $bcc, $failedRecipients);
        } finally {
            $message->setBcc($bcc);
        }

        if ($evt) {
            if ($sent == \count($to) + \count($cc) + \count($bcc)) {
                $evt->setResult(Swift_Events_SendEvent::RESULT_SUCCESS);
            } elseif ($sent > 0) {
                $evt->setResult(Swift_Events_SendEvent::RESULT_TENTATIVE);
            } else {
                $evt->setResult(Swift_Events_SendEvent::RESULT_FAILED);
            }
            $evt->setFailedRecipients($failedRecipients);
            $this->eventDispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        $message->generateId(); //Make sure a new Message ID is used

        try {
            // Get Postfix Queue ID and store in the database
            $id = str_replace("\r\n", "", Str::after($logger->dump(), 'Ok: queued as '));

            PostfixQueueId::create([
                'queue_id' => $id
            ]);
        } catch (QueryException $e) {
            // duplicate entry
        }

        return $sent;
    }

    /** Send a message to the given To: recipients */
    private function sendTo(Swift_Mime_SimpleMessage $message, $reversePath, array $to, array &$failedRecipients)
    {
        if (empty($to)) {
            return 0;
        }

        return $this->doMailTransaction(
            $message,
            $reversePath,
            array_keys($to),
            $failedRecipients
        );
    }

    /** Send a message to all Bcc: recipients */
    private function sendBcc(Swift_Mime_SimpleMessage $message, $reversePath, array $bcc, array &$failedRecipients)
    {
        $sent = 0;
        foreach ($bcc as $forwardPath => $name) {
            $message->setBcc([$forwardPath => $name]);
            $sent += $this->doMailTransaction(
                $message,
                $reversePath,
                [$forwardPath],
                $failedRecipients
            );
        }

        return $sent;
    }

    /** Send an email to the given recipients from the given reverse path */
    private function doMailTransaction($message, $reversePath, array $recipients, array &$failedRecipients)
    {
        $sent = 0;
        $this->doMailFromCommand($reversePath);
        foreach ($recipients as $forwardPath) {
            try {
                $this->doRcptToCommand($forwardPath);
                ++$sent;
            } catch (Swift_TransportException $e) {
                $failedRecipients[] = $forwardPath;
            } catch (Swift_AddressEncoderException $e) {
                $failedRecipients[] = $forwardPath;
            }
        }

        if (0 != $sent) {
            $sent += \count($failedRecipients);
            $this->doDataCommand($failedRecipients);
            $sent -= \count($failedRecipients);

            $this->streamMessage($message);
        } else {
            $this->reset();
        }

        return $sent;
    }
}
