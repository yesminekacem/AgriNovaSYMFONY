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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET','POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UrlGeneratorInterface $urlGenerator, UserRepository $userRepository, ValidatorInterface $validator): Response
    {
        // If GET, just render the form (no errors / previous values)
        $formValues = ['email' => '', 'fullName' => ''];
        $formErrors = [];

        if ($request->isMethod('POST')) {
            // CSRF check
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

            // Basic presence checks
            if (!$email) {
                $formErrors['email'] = 'Email is required.';
            }

            if (!$plainPassword) {
                $formErrors['password'] = 'Password is required.';
            }

            if (!$confirmPassword) {
                $formErrors['confirm_password'] = 'Please confirm your password.';
            }

            // Password match
            if ($plainPassword && $confirmPassword && $plainPassword !== $confirmPassword) {
                $formErrors['confirm_password'] = 'Passwords do not match.';
            }

            // Password format rules: min 8 chars, 1 upper, 1 lower, 1 digit, 1 special char
            if ($plainPassword) {
                $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';
                if (!preg_match($pattern, $plainPassword)) {
                    $formErrors['password'] = 'Password must be at least 8 characters and include upper and lower case letters, a number and a special character.';
                }
            }

            // Validate email format using Symfony Validator on the User entity
            if (!isset($formErrors['email'])) {
                $violations = $validator->validatePropertyValue(\App\Entity\User::class, 'email', $email);
                if (count($violations) > 0) {
                    $formErrors['email'] = $violations[0]->getMessage();
                }
            }

            // Prevent duplicate emails
            if (!isset($formErrors['email'])) {
                $existing = $userRepository->findOneBy(['email' => $email]);
                if ($existing) {
                    $formErrors['email'] = 'An account with this email already exists.';
                }
            }

            // Check if email already exists using DQL (keeps current behavior but is optional)
            if (!isset($formErrors['email'])) {
                $query = $em->createQuery(
                    'SELECT COUNT(u.id) FROM App\\Entity\\User u WHERE u.email = :email'
                )->setParameter('email', $email);

                $emailCount = $query->getSingleScalarResult();

                if ($emailCount > 0) {
                    $formErrors['email'] = 'This email is already registered.';
                }
            }

            if (count($formErrors) > 0) {
                // Render form with errors and previously entered values
                return $this->render('Front/register.html.twig', [
                    'formValues' => $formValues,
                    'formErrors' => $formErrors,
                ]);
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

        return $this->render('Front/register.html.twig', [
            'formValues' => $formValues,
            'formErrors' => $formErrors,
        ]);
    }
}
