<?php

namespace App\Service;

use App\Entity\Rental;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class RentalNotificationService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, Environment $twig, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function notifyStatusChange(Rental $rental, string $oldStatus, string $newStatus): void
    {
        if (!in_array($newStatus, ['APPROVED', 'ACTIVE', 'CANCELLED'], true)) {
            return;
        }

        $this->sendRentalEmail(
            $rental,
            $this->buildStatusSubject($rental, $newStatus),
            'emails/rental_status_change.html.twig',
            [
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus,
            ]
        );
    }

    public function notifyOverdue(Rental $rental): void
    {
        $this->sendRentalEmail(
            $rental,
            sprintf('Rental #%d is overdue', $rental->getId()),
            'emails/rental_overdue.html.twig',
            ['daysOverdue' => abs($rental->getDaysRemaining())]
        );
    }

    public function notifyUpcomingReturn(Rental $rental, int $daysRemaining): void
    {
        $this->sendRentalEmail(
            $rental,
            sprintf('Reminder: rental #%d is due in %d day(s)', $rental->getId(), $daysRemaining),
            'emails/rental_reminder.html.twig',
            ['daysRemaining' => $daysRemaining]
        );
    }

    public function notifyNewRental(Rental $rental): void
    {
        $this->sendRentalEmail(
            $rental,
            sprintf('We received your rental request for %s', $rental->getDisplayItemName()),
            'emails/rental_created.html.twig'
        );
    }

    private function sendRentalEmail(Rental $rental, string $subject, string $template, array $context = []): void
    {
        $recipient = trim((string) $rental->getRenterContact());
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->logger->warning(sprintf(
                'Rental email skipped for rental #%d because "%s" is not a valid email address.',
                $rental->getId() ?? 0,
                $recipient
            ));

            return;
        }

        try {
            $email = (new Email())
                ->from(new Address($this->getFromAddress(), $this->getFromName()))
                ->to($recipient)
                ->subject($subject)
                ->html($this->twig->render($template, array_merge($context, [
                    'rental' => $rental,
                ])));

            $this->mailer->send($email);
            $this->logger->info(sprintf('Rental email sent for rental #%d with subject "%s".', $rental->getId(), $subject));
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(sprintf('Failed to send rental email for rental #%d: %s', $rental->getId(), $e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Unexpected error sending rental email for rental #%d: %s', $rental->getId(), $e->getMessage()));
        }
    }

    private function buildStatusSubject(Rental $rental, string $newStatus): string
    {
        return match ($newStatus) {
            'APPROVED' => sprintf('Your rental #%d has been approved', $rental->getId()),
            'ACTIVE' => sprintf('Your rental #%d is now active', $rental->getId()),
            'CANCELLED' => sprintf('Your rental #%d has been cancelled', $rental->getId()),
            default => sprintf('Rental #%d status updated', $rental->getId()),
        };
    }

    private function getFromAddress(): string
    {
        return $_ENV['MAILER_FROM_ADDRESS_2'] ?? 'noreply@agrinova.com';
    }

    private function getFromName(): string
    {
        return $_ENV['MAILER_FROM_NAME_2'] ?? 'AgriNova Rentals';
    }
}
