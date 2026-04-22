<?php

namespace App\Controller;

use App\Entity\User;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class PasswordRecoveryController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function requestReset(
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        #[Autowire(service: 'cache.app')] CacheItemPoolInterface $cache
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot_password', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $email = strtolower(trim((string) $request->request->get('email', '')));
            if ($email === '') {
                $this->addFlash('error', 'Email is required.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $code = (string) random_int(100000, 999999);
                $item = $cache->getItem($this->resetCacheKey($email));
                $item->set([
                    'email' => $email,
                    'code_hash' => $this->hashCode($email, $code),
                ]);
                $item->expiresAfter(900);
                $cache->save($item);

                $this->sendCodeEmail(
                    $mailer,
                    $email,
                    $code,
                    'Your AGRINOVA password reset code',
                    'Use this code to reset your password: '
                );
            }

            // Avoid account enumeration by using same message for both cases.
            $this->addFlash('success', 'If the email exists, a reset code has been sent.');
            return $this->redirectToRoute('app_reset_password', ['email' => $email]);
        }

        return $this->render('Front/forgot_password.html.twig');
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        #[Autowire(service: 'cache.app')] CacheItemPoolInterface $cache
    ): Response {
        $email = strtolower(trim((string) ($request->request->get('email') ?? $request->query->get('email', ''))));

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            $code = trim((string) $request->request->get('code', ''));
            $newPassword = (string) $request->request->get('password', '');
            $confirmPassword = (string) $request->request->get('confirm_password', '');

            if ($email === '' || $code === '' || $newPassword === '' || $confirmPassword === '') {
                $this->addFlash('error', 'All fields are required.');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $newPassword)) {
                $this->addFlash('error', 'Password must be at least 8 characters and include upper and lower case letters, a number and a special character.');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            $user = $userRepository->findOneBy(['email' => $email]);
            if (!$user instanceof User) {
                $this->addFlash('error', 'Invalid reset request.');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            $item = $cache->getItem($this->resetCacheKey($email));
            $payload = $item->isHit() ? $item->get() : null;
            if (!is_array($payload) || !isset($payload['code_hash'])) {
                $this->addFlash('error', 'Reset code expired. Request a new one.');
                return $this->redirectToRoute('app_forgot_password');
            }

            if (!hash_equals($payload['code_hash'], $this->hashCode($email, $code))) {
                $this->addFlash('error', 'Invalid reset code.');
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();
            $cache->deleteItem($this->resetCacheKey($email));

            $this->addFlash('success', 'Password updated successfully. Please sign in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('Front/reset_password.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/reset-password/resend', name: 'app_reset_password_resend', methods: ['POST'])]
    public function resendResetCode(
        Request $request,
        UserRepository $userRepository,
        MailerInterface $mailer,
        #[Autowire(service: 'cache.app')] CacheItemPoolInterface $cache
    ): Response {
        $email = strtolower(trim((string) $request->request->get('email', '')));

        if (!$this->isCsrfTokenValid('reset_password_resend', $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_reset_password', ['email' => $email]);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if ($user) {
            $code = (string) random_int(100000, 999999);
            $item = $cache->getItem($this->resetCacheKey($email));
            $item->set([
                'email' => $email,
                'code_hash' => $this->hashCode($email, $code),
            ]);
            $item->expiresAfter(900);
            $cache->save($item);

            $this->sendCodeEmail(
                $mailer,
                $email,
                $code,
                'Your AGRINOVA password reset code',
                'Use this code to reset your password: '
            );
        }

        $this->addFlash('success', 'If the email exists, a new reset code has been sent.');
        return $this->redirectToRoute('app_reset_password', ['email' => $email]);
    }

    private function resetCacheKey(string $email): string
    {
        return 'pwd_reset_' . sha1(strtolower(trim($email)));
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
            $this->addFlash('error', 'Unable to send the password reset email: ' . $e->getMessage());
        }
    }
}

