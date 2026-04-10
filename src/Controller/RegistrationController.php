<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET','POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UrlGeneratorInterface $urlGenerator, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            // CSRF check
            if (!$this->isCsrfTokenValid('register', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_register');
            }

            $email = trim($request->request->get('email', ''));
            $fullName = trim($request->request->get('fullName', ''));
            $plainPassword = $request->request->get('password', '');

            if (!$email || !$plainPassword) {
                $this->addFlash('error', 'Email and password are required.');
                return $this->redirectToRoute('app_register');
            }

            // Prevent duplicate emails
            $existing = $userRepository->findOneBy(['email' => $email]);
            if ($existing) {
                $this->addFlash('error', 'An account with this email already exists.');
                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setFullName($fullName ?: null);

            // Hash the password
            $hashed = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);

            // mark as not verified (uses existing `email_verified` column)
            $user->setIsVerified(false);

            $em->persist($user);
            $em->flush();

            // Generate a stateless verification token (HMAC) so we do not need an extra DB column.
            $appSecret = $_ENV['APP_SECRET'] ?? getenv('APP_SECRET') ?? 'dev_secret';
            $token = hash_hmac('sha256', $email . ':' . $hashed, $appSecret);

            $verificationUrl = $urlGenerator->generate('app_verify_email', ['email' => $email, 'token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            // For local dev we flash the verification URL so you can open it.
            $this->addFlash('success', 'Account created. Verify your email using this link: ' . $verificationUrl);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('Front/register.html.twig');
    }
}
