<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'app_verify_email')]
    public function verify(Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $email = $request->query->get('email');
        $token = $request->query->get('token');

        if (!$email || !$token) {
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $this->addFlash('error', 'No account found for this verification link.');
            return $this->redirectToRoute('app_register');
        }

        // Build expected token: HMAC of email + stored password hash with APP_SECRET
        $appSecret = $this->resolveAppSecret();
        $expected = hash_hmac('sha256', $user->getEmail() . ':' . $user->getPassword(), $appSecret);

        if (!hash_equals($expected, $token)) {
            $this->addFlash('error', 'Verification token is invalid or expired.');
            return $this->redirectToRoute('app_register');
        }

        // mark verified using existing column mapped to isVerified
        $user->setIsVerified(true);
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Your email has been verified. You can now sign in.');
        return $this->redirectToRoute('app_login');
    }

    private function resolveAppSecret(): string
    {
        $appSecret = getenv('APP_SECRET');
        if (is_string($appSecret) && $appSecret !== '') {
            return $appSecret;
        }

        if (array_key_exists('APP_SECRET', $_ENV) && is_string($_ENV['APP_SECRET']) && $_ENV['APP_SECRET'] !== '') {
            return $_ENV['APP_SECRET'];
        }

        return 'dev_secret';
    }
}
