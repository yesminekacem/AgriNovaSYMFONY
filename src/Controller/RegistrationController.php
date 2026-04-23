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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET','POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        ValidatorInterface $validator,
        MailerInterface $mailer,
        #[Autowire(service: 'cache.app')] CacheItemPoolInterface $cache
    ): Response {
        $formValues = ['email' => '', 'fullName' => ''];
        $formErrors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('register', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_register');
            }

            $email = trim((string) $request->request->get('email', ''));
            $fullName = trim((string) $request->request->get('fullName', ''));
            $plainPassword = (string) $request->request->get('password', '');
            $confirmPassword = (string) $request->request->get('confirm_password', '');

            $formValues['email'] = $email;
            $formValues['fullName'] = $fullName;

            if (!$email) {
                $formErrors['email'] = 'Email is required.';
            }
            if (!$plainPassword) {
                $formErrors['password'] = 'Password is required.';
            }
            if (!$confirmPassword) {
                $formErrors['confirm_password'] = 'Please confirm your password.';
            }
            if ($plainPassword && $confirmPassword && $plainPassword !== $confirmPassword) {
                $formErrors['confirm_password'] = 'Passwords do not match.';
            }

            if ($plainPassword) {
                $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';
                if (!preg_match($pattern, $plainPassword)) {
                    $formErrors['password'] = 'Password must be at least 8 characters and include upper and lower case letters, a number and a special character.';
                }
            }

            if (!isset($formErrors['email'])) {
                $violations = $validator->validatePropertyValue(User::class, 'email', $email);
                if (count($violations) > 0) {
                    $formErrors['email'] = $violations[0]->getMessage();
                }
            }

            if (!isset($formErrors['email'])) {
                $existing = $userRepository->findOneBy(['email' => $email]);
                if ($existing) {
                    $formErrors['email'] = 'An account with this email already exists.';
                }
            }

            if (count($formErrors) > 0) {
                return $this->render('Front/register.html.twig', [
                    'formValues' => $formValues,
                    'formErrors' => $formErrors,
                ]);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setFullName($fullName ?: null);
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setIsVerified(false);

            $em->persist($user);
            $em->flush();

            $code = (string) random_int(100000, 999999);
            $cacheItem = $cache->getItem('email_verify_' . $user->getId());
            $cacheItem->set([
                'email' => strtolower($email),
                'code_hash' => $this->hashCode($email, $code),
            ]);
            $cacheItem->expiresAfter(900);
            $cache->save($cacheItem);

            $this->sendCodeEmail($mailer, $email, $code, 'Verify your AGRINOVA account', 'Use this verification code to activate your account: ');

            $this->addFlash('success', 'Account created. We sent a verification code to your email.');
            return $this->redirectToRoute('app_verify_email', ['email' => $email]);
        }

        return $this->render('Front/register.html.twig', [
            'formValues' => $formValues,
            'formErrors' => $formErrors,
        ]);
    }

    private function hashCode(string $email, string $code): string
    {
        $secret = $_ENV['APP_SECRET'] ?? (string) getenv('APP_SECRET') ?: 'dev_secret';
        return hash_hmac('sha256', strtolower(trim($email)) . '|' . $code, $secret);
    }

    private function sendCodeEmail(MailerInterface $mailer, string $to, string $code, string $subject, string $prefix): void
    {
        $from = $_ENV['MAILER_FROM_2'] ?? (string) getenv('MAILER_FROM_2') ?: 'no-reply@agrinova.local';
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
