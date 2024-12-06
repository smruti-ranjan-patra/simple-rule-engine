<?php

namespace App\Service;

use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;

class NotifierService
{
    private $notifier;

    public function __construct(NotifierInterface $notifier)
    {
        $this->notifier = $notifier;
    }


    /**
     * Send an email to a recipient with a message.
     *
     * @param string $message The message to send to the recipient
     * @param string $recipientEmail The email address of the recipient
     */
    public function notify(string $message, string $recipientEmail)
    {
        $recipient = new Recipient($recipientEmail);
        $emailMessage = new EmailMessage($message);
        $this->notifier->send($emailMessage, $recipient);
    }
    /**
     * Notify a Slack channel with a message.
     *
     * @param string $message The message to send to the Slack channel
     * @param string $slackChannel The Slack channel to notify
     * @param array $tags An array of tags to associate with the message
     */

    public function notifySlack(string $message, string $slackChannel, array $tags = [])
    {
        $chatMessage = new ChatMessage($message);
        $chatMessage->transport('slack');
        $this->notifier->send($chatMessage, new Recipient($slackChannel));
    }
}
