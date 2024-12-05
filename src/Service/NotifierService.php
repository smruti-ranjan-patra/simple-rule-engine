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

    public function notify(string $message, string $recipientEmail)
    {
        $recipient = new Recipient($recipientEmail);
        $emailMessage = new EmailMessage($message);
        $this->notifier->send($emailMessage, $recipient);
    }

    public function notifySlack(string $message, string $slackChannel)
    {
        $chatMessage = new ChatMessage($message);
        $chatMessage->transport('slack');
        $this->notifier->send($chatMessage, new Recipient($slackChannel));
    }
}
