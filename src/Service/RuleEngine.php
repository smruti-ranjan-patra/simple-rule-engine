<?php

namespace App\Service;

class RuleEngine
{
    private $notifierService;

    public function __construct(NotifierService $notifierService)
    {
        $this->notifierService = $notifierService;
    }

    /**
     * Evaluate rules based on the scan results and trigger appropriate actions.
     *
     * @param array $scanResult
     */
    public function evaluateRules(array $scanResult)
    {
        if ($scanResult['vulnerabilities'] > 25) {
            $message = "{$scanResult['vulnerabilities']} vulnerabilities found in your scan.";
            $this->handleAction('send_email', [
                'email' => 'user@example.com',
                'message' => $message
            ]);
            $this->handleAction('send_slack_message', [
                'channel' => 'alert-vulnerabilities',
                'tag' => ['dev', 'support'],
                'message' => $message
            ]);
        } elseif ($scanResult['upload-in-progress'] > 0) {
            $message = "{$scanResult['upload-in-progress']} files are in upload process";
            $this->handleAction('send_email', [
                'email' => 'user@example.com',
                'message' => $message
            ]);
            $this->handleAction('send_slack_message', [
                'channel' => 'info-stats',
                'tag' => ['support'],
                'message' => $message
            ]);
        } elseif ($scanResult['upload-failed']) {
            $message = "{$scanResult['upload-failed']} files failed to be uploaded";
            $this->handleAction('send_email', [
                'email' => 'user@example.com',
                'message' => $message
            ]);
            $this->handleAction('send_slack_message', [
                'channel' => 'alert-failure',
                'tag' => ['support'],
                'message' => $message
            ]);
        }
    }

    /**
     * Handle actions based on the triggered rule.
     *
     * @param string $action
     * @param array $params
     */
    private function handleAction(string $action, array $params)
    {
        switch ($action) {
            case 'send_email':
                $this->notifierService->notify($params['message'], $params['email']);
                break;

            case 'send_slack_message':
                $this->notifierService->notifySlack($params['message'], $params['channel'], $params['tag']);
                // Code to send a Slack message; slack channel and tagging data
                break;

        }
    }
}
