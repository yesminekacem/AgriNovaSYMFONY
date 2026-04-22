<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class VerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET', 'POST'])]
    public function verify(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        #[Autowire(service: 'cache.app')] CacheItemPoolInterface $cache
    ): Response {
        $email = strtolower(trim((string) ($request->request->get('email') ?? $request->query->get('email', ''))));

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('verify_email_code', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_verify_email', ['email' => $email]);
            }

            $code = trim((string) $request->request->get('code', ''));

            if ($email === '' || $code === '') {
                $this->addFlash('error', 'Email and verification code are required.');
                return $this->redirectToRoute('app_verify_email', ['email' => $email]);
            }

            $user = $userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                $this->addFlash('error', 'No account found for this email.');
                return $this->redirectToRoute('app_verify_email', ['email' => $email]);
            }

            if ($user->isVerified()) {
                $this->addFlash('success', 'Your email is already verified.');
                return $this->redirectToRoute('app_login');
            }

            $item = $cache->getItem('email_verify_' . $user->getId());
            $payload = $item->isHit() ? $item->get() : null;
            if (!is_array($payload) || !isset($payload['code_hash'])) {
                $this->addFlash('error', 'Verification code expired. Please request a new one.');
                return $this->redirectToRoute('app_verify_email', ['email' => $email]);
            }

            $expected = $payload['code_hash'];
            if (!hash_equals($expected, $this->hashCode($email, $code))) {
                $this->addFlash('error', 'Invalid verification code.');
                return $this->redirectToRoute('app_verify_email', ['email' => $email]);
            }

            $user->setIsVerified(true);
            $em->flush();
            $cache->deleteItem('email_verify_' . $user->getId());

            $this->addFlash('success', 'Your email has been verified. You can now sign in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('Front/verify_email_code.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/verify-email/resend', name: 'app_verify_email_resend', methods: ['POST'])]
    public function resend(
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        #[Autowire(service: 'cache.app')] CacheItemPoolInterface $cache
    ): Response {
        $email = strtolower(trim((string) $request->request->get('email', '')));

        if (!$this->isCsrfTokenValid('verify_email_resend', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_verify_email', ['email' => $email]);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $this->addFlash('error', 'No account found for this email.');
            return $this->redirectToRoute('app_verify_email', ['email' => $email]);
        }

        if ($user->isVerified()) {
            $this->addFlash('success', 'Your email is already verified.');
            return $this->redirectToRoute('app_login');
        }

        $code = (string) random_int(100000, 999999);
        $item = $cache->getItem('email_verify_' . $user->getId());
        $item->set([
            'email' => $email,
            'code_hash' => $this->hashCode($email, $code),
        ]);
        $item->expiresAfter(900);
        $cache->save($item);

        $this->sendCodeEmail($mailer, $email, $code, 'Your AGRINOVA verification code', 'Use this verification code to activate your account: ');
        $this->addFlash('success', 'A new verification code was sent.');

        return $this->redirectToRoute('app_verify_email', ['email' => $email]);
    }

    private function hashCode(string $email, string $code): string
    {
        $secret = $_ENV['APP_SECRET'] ?? (string) getenv('APP_SECRET') ?: 'dev_secret';
        return hash_hmac('sha256', strtolower(trim($email)) . '|' . $code, $secret);
    }

    private function sendCodeEmail(MailerInterface $mailer, string $to, string $code, string $subject, string $prefix): void
    {
        $from = $_ENV['MAILER_FROM'] ?? (string) getenv('MAILER_FROM') ?: 'no-reply@agrinova.local';
        $email = (new Email())
            ->from(Address::create($from))
            ->to($to)
            ->subject($subject)
            ->text($prefix . $code . "\n\nThis code expires in 15 minutes.");

        try {
            $mailer->send($email);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Unable to send the verification email: ' . $e->getMessage());
        }
    }
}
