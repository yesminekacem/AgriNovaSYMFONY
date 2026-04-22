<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
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

class SecurityController extends AbstractController
{
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
    public function faceLogin(Request $request, FaceApiClient $faceApiClient, UserRepository $userRepository, TokenStorageInterface $tokenStorage, EventDispatcherInterface $dispatcher): Response
    {
        $user = $this->getUser();
        if ($user) {
            // already logged in
            return $this->redirectToRoute('app_profile');
        }

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));
            $probeFile = $request->files->get('face_image');
            $probeBase64 = trim((string) $request->request->get('face_image_base64', '')) ?: null;
            $probe = $probeFile ?? $probeBase64;

            if (!$email || !$probe) {
                $this->addFlash('error', 'Email and face image (camera capture or upload) are required.');
                return $this->redirectToRoute('app_login_face');
            }

            $candidate = $userRepository->findOneBy(['email' => $email]);
            $enrolledData = $candidate ? $candidate->getFaceData() : null;
            if (!$candidate || !$candidate->isFaceEnrolled() || !$enrolledData) {
                $this->addFlash('error', 'No enrolled face found for that email.');
                return $this->redirectToRoute('app_login_face');
            }

            // enrolledData is expected to be a base64 string (stored in face_data).
            $result = $faceApiClient->verify($probe, $enrolledData);
            // Accept if provider returned a confidence > threshold. Face++ typical thresholds: 60-80; choose 70
            $threshold = 70.0;
            $score = $result['score'] ?? null;

            if ($result['success'] && $score !== null && (float)$score >= $threshold) {
                // programmatically authenticate the user
                $token = new UsernamePasswordToken($candidate, 'main', $candidate->getRoles());
                $tokenStorage->setToken($token);
                $session = $request->getSession();
                if ($session) {
                    $session->set('_security_main', serialize($token));
                }
                // Dispatch interactive login event so other listeners can react
                $event = new InteractiveLoginEvent($request, $token);
                $dispatcher->dispatch($event, 'security.interactive_login');

                $this->addFlash('success', 'Face recognized. Logged in.');
                return $this->redirectToRoute('app_profile');
            }

            $this->addFlash('error', 'Face not recognized.');
            return $this->redirectToRoute('app_login_face');
        }

        return $this->render('Front/face_login.html.twig');
    }

    #[Route('/login/face/detect', name: 'app_login_face_detect', methods: ['POST'])]
    public function detectFace(
        Request $request,
        UserRepository $userRepository,
        FaceApiClient $faceApiClient,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $dispatcher
    ): Response {
        $faceBase64 = $request->request->get('face_image_base64');
        if (!$faceBase64) {
            return new JsonResponse(['success' => false, 'message' => 'No face data provided.'], 400);
        }

        // Compare the probe image against all enrolled users' face data
        $threshold = 70.0; // confidence threshold
        $probe = $faceBase64;
        $candidates = [];

        $users = $userRepository->findAllWithFaceData();
        if (count($users) === 0) {
            return new JsonResponse(['success' => false, 'code' => 'no_enrolled', 'message' => 'No enrolled faces available.']);
        }

        foreach ($users as $u) {
            $enrolled = $u->getFaceData();
            if (!$enrolled) continue;
            $result = $faceApiClient->verify($probe, $enrolled);
            $score = $result['score'] ?? null;

            // If face API returned a positive match use it
            if ($result['success'] && $score !== null && (float)$score >= $threshold) {
                $candidates[] = ['user' => $u, 'score' => (float)$score];
                continue;
            }

            // Fallback: if external API not configured or returned error, try a simple similarity heuristic
            // Decode base64 (strip any data URI prefix)
            $decodedProbe = $probe;
            if (str_starts_with($decodedProbe, 'data:')) {
                $parts = explode(',', $decodedProbe, 2);
                $decodedProbe = $parts[1] ?? '';
            }
            $decodedEnrolled = $enrolled;
            if (str_starts_with($decodedEnrolled, 'data:')) {
                $parts = explode(',', $decodedEnrolled, 2);
                $decodedEnrolled = $parts[1] ?? '';
            }

            $binaryProbe = base64_decode($decodedProbe, true);
            $binaryEnrolled = base64_decode($decodedEnrolled, true);

            if ($binaryProbe !== false && $binaryEnrolled !== false) {
                // Use similar_text percentage as a naive similarity metric
                $percent = 0.0;
                // To avoid huge memory usage convert to shorter representation: use substrings or hashes
                // We'll compare the first N bytes and last N bytes to approximate similarity
                $N = 2000; // compare up to first/last 2000 bytes
                $a = substr($binaryProbe, 0, $N) . substr($binaryProbe, max(0, strlen($binaryProbe) - $N), $N);
                $b = substr($binaryEnrolled, 0, $N) . substr($binaryEnrolled, max(0, strlen($binaryEnrolled) - $N), $N);
                similar_text($a, $b, $percent);
                if ($percent >= 60.0) {
                    $candidates[] = ['user' => $u, 'score' => $percent];
                }
            }
        }

        if (count($candidates) === 0) {
            $accept = $request->headers->get('accept', '');
            $isAjax = $request->isXmlHttpRequest() || str_contains($accept, 'application/json');
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'code' => 'no_match', 'message' => 'No matching accounts found.']);
            }
            $this->addFlash('error', 'No matching accounts found.');
            return $this->redirectToRoute('app_login');
        }

        if (count($candidates) === 1) {
            // Programmatically authenticate the matched user so that redirect lands on authenticated dashboard
            $matched = $candidates[0]['user'];
            $token = new UsernamePasswordToken($matched, 'main', $matched->getRoles());
            // set token in storage
            $tokenStorage->setToken($token);
            // store token in session so Symfony recognizes it on subsequent requests
            $session = $request->getSession();
            if ($session) {
                try { if (!$session->isStarted()) { $session->start(); } } catch (\Throwable $e) { /* ignore */ }
                $session->set('_security_main', serialize($token));
                try { $session->save(); } catch (\Throwable $e) { /* ignore save errors */ }
            }
            // Dispatch interactive login event so other listeners can react
            $event = new InteractiveLoginEvent($request, $token);
            $dispatcher->dispatch($event, 'security.interactive_login');

            // Include debug info so client can confirm server-side session/token
            $debug = [
                'sessionId' => $session ? $session->getId() : null,
                'userId' => $matched->getId(),
                'tokenSet' => true,
            ];

            // If request expects JSON (AJAX), return JSON; otherwise perform a normal redirect so browser follows it and cookies are set.
            $accept = $request->headers->get('accept', '');
            $isAjax = $request->isXmlHttpRequest() || str_contains($accept, 'application/json');
            if ($isAjax) {
                return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_dashboard'), 'debug' => $debug]);
            }

            // Non-AJAX: redirect normally
            return $this->redirect($this->generateUrl('app_dashboard'));
        }

        // Multiple matches -> return list with scores (AJAX) or redirect back with a flash message (non-AJAX)
        $accept = $request->headers->get('accept', '');
        $isAjax = $request->isXmlHttpRequest() || str_contains($accept, 'application/json');
        $matches = array_map(function ($c) {
             $u = $c['user'];
             return [
                 'id' => $u->getId(),
                 'fullName' => $u->getFullName(),
                 'score' => round($c['score'], 2),
             ];
        }, $candidates);

        if ($isAjax) {
            return new JsonResponse(['success' => true, 'matches' => $matches]);
        }

        // Non-AJAX: prompt user to try normal face login page where selection can be made
        $this->addFlash('info', 'Multiple possible matches found. Please use the Face Login page for selection.');
        return $this->redirectToRoute('app_login_face');
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
            'isAuthenticated' => $user ? true : false,
            'userId' => $user ? $user->getId() : null,
            'sessionId' => $session ? $session->getId() : null,
        ];
        return new JsonResponse($data);
    }
}
