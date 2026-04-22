<?php

namespace App\Service;

use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

class MailtrapService
{
    private MailtrapClient $mailtrap;

    public function __construct(string $apiKey)
    {
        $this->mailtrap = MailtrapClient::initSendingEmails(apiKey: $apiKey);
    }

    public function sendEmail(string $to, string $subject, string $text, string $html = null): array
    {
        $email = (new MailtrapEmail())
            ->from(new Address('noreply@agrinova.com', 'AgriNova System'))
            ->to(new Address($to))
            ->subject($subject)
            ->text($text);

        if ($html) {
            $email->html($html);
        }

        $response = $this->mailtrap->send($email);
        return ResponseHelper::toArray($response);
    }
}
