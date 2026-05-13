<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Service\FaceApiClient;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginFormAuthenticator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SecurityController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.face_id')]
        private readonly LoggerInterface $faceLogger,
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect based on role
        $user = $this->getUser();
        if ($user) {
            $roles = $user->getRoles();

            if (in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('admin_users');
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('Front/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/login/face', name: 'app_login_face', methods: ['GET','POST'])]
    public function faceLogin(): Response
    {
        $this->faceLogger->info('Legacy /login/face endpoint hit; redirecting to /login.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/login/face/detect', name: 'app_login_face_detect', methods: ['POST'])]
    public function detectFace(
        Request $request,
        UserRepository $userRepository,
        FaceApiClient $faceApiClient,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $dispatcher
    ): Response {
        $this->faceLogger->info('Face login detection started.');
        $faceBase64 = $request->request->get('face_image_base64');
        if (!$faceBase64) {
            $this->faceLogger->warning('Face login detection failed: missing face_image_base64 payload.');
            return new JsonResponse(['success' => false, 'message' => 'No face data provided.'], 400);
        }

        // Compare the probe image against all enrolled users' face data using strict provider-only matching.
        $threshold = 78.0;
        $minWinningGap = 6.0;
        $probe = $faceBase64;
        $candidates = [];

        $users = $userRepository->findAllWithFaceData();
        $this->faceLogger->info('Loaded enrolled users for face matching.', ['count' => count($users)]);
        if (count($users) === 0) {
            $this->faceLogger->warning('Face login detection aborted: no enrolled faces in database.');
            return new JsonResponse(['success' => false, 'code' => 'no_enrolled', 'message' => 'No enrolled faces available.']);
        }

        foreach ($users as $u) {
            $enrolled = $u->getFaceData();
            if (!$enrolled) {
                continue;
            }

            $result = $faceApiClient->verify($probe, $enrolled);
            $score = $result['score'] ?? null;
            $this->faceLogger->debug('Face comparison finished.', [
                'userId' => $u->getId(),
                'success' => (bool) ($result['success'] ?? false),
                'score' => $score,
            ]);

            if ($result['success'] && $score !== null && (float)$score >= $threshold) {
                $candidates[] = ['user' => $u, 'score' => (float)$score];
            }
        }

        usort($candidates, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        if (count($candidates) === 0) {
            $this->faceLogger->info('Face login result: no matching account.');
            $accept = $request->headers->get('accept', '');
            $isAjax = $request->isXmlHttpRequest() || str_contains($accept, 'application/json');
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'code' => 'no_match', 'message' => 'No matching accounts found.']);
            }
            $this->addFlash('error', 'No matching accounts found.');
            return $this->redirectToRoute('app_login');
        }

        if (count($candidates) > 1) {
            $bestScore = $candidates[0]['score'];
            $secondScore = $candidates[1]['score'];
            $gap = $bestScore - $secondScore;

            if ($gap < $minWinningGap) {
                $this->faceLogger->warning('Face login result ambiguous; refusing authentication.', [
                    'bestScore' => $bestScore,
                    'secondScore' => $secondScore,
                    'gap' => $gap,
                    'requiredGap' => $minWinningGap,
                ]);
                $accept = $request->headers->get('accept', '');
                $isAjax = $request->isXmlHttpRequest() || str_contains($accept, 'application/json');
                if ($isAjax) {
                    return new JsonResponse(['success' => false, 'code' => 'no_match', 'message' => 'No matching accounts found.']);
                }
                $this->addFlash('error', 'No matching accounts found.');
                return $this->redirectToRoute('app_login');
            }
        }

        $matched = $candidates[0]['user'];
        $matchedScore = $candidates[0]['score'];
        $token = new UsernamePasswordToken($matched, 'main', $matched->getRoles());
        $tokenStorage->setToken($token);
        $session = $request->getSession();
        if ($session) {
            try {
                if (!$session->isStarted()) {
                    $session->start();
                }
            } catch (\Throwable) {
            }
            $session->set('_security_main', serialize($token));
            try {
                $session->save();
            } catch (\Throwable) {
            }
        }

        $event = new InteractiveLoginEvent($request, $token);
        $dispatcher->dispatch($event, 'security.interactive_login');

        $redirectRoute = in_array('ROLE_ADMIN', $matched->getRoles(), true) ? 'admin_users' : 'app_profile';
        $debug = [
            'sessionId' => $session ? $session->getId() : null,
            'userId' => $matched->getId(),
            'tokenSet' => true,
            'score' => round($matchedScore, 2),
        ];
        $this->faceLogger->info('Face login success.', [
            'userId' => $matched->getId(),
            'score' => round($matchedScore, 2),
            'redirectRoute' => $redirectRoute,
        ]);

        $accept = $request->headers->get('accept', '');
        $isAjax = $request->isXmlHttpRequest() || str_contains($accept, 'application/json');
        if ($isAjax) {
            return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl($redirectRoute), 'debug' => $debug]);
        }

        return $this->redirectToRoute($redirectRoute);
    }

    #[Route('/login/face/select', name: 'app_login_face_select', methods: ['POST'])]
    public function selectAccount(
        Request $request,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $dispatcher
    ): JsonResponse {
        $userId = $request->request->get('selected_user');
        $user = $userRepository->find($userId);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found.'], 404);
        }

        // Programmatically authenticate the selected user
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage->setToken($token);
        $session = $request->getSession();
        if ($session) {
            try { if (!$session->isStarted()) { $session->start(); } } catch (\Throwable $e) { /* ignore */ }
            $session->set('_security_main', serialize($token));
            try { $session->save(); } catch (\Throwable $e) { /* ignore */ }
        }
        $event = new InteractiveLoginEvent($request, $token);
        $dispatcher->dispatch($event, 'security.interactive_login');

        $debug = [
            'sessionId' => $session ? $session->getId() : null,
            'userId' => $user->getId(),
            'tokenSet' => true,
        ];

        return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_dashboard'), 'debug' => $debug]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/_auth_status', name: 'app_auth_status', methods: ['GET'])]
    public function authStatus(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $user = $this->getUser();
        $data = [
            'isAuthenticated' => $user !== null,
            'userId' => $user instanceof User ? $user->getId() : null,
            'sessionId' => $session ? $session->getId() : null,
        ];
        return new JsonResponse($data);
    }
}
