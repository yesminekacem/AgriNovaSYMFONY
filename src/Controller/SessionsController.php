<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SessionsController extends AbstractController
{
    #[Route('/sessions', name: 'app_sessions', methods: ['GET'])]
    public function sessions(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $session = $request->getSession();
        $sessionData = [];
        foreach ($session->all() as $k => $v) {
            if (in_array($k, ['_security_main', 'flash'], true)) {
                continue;
            }
            $sessionData[$k] = $v;
        }

        return $this->render('Front/sessions.html.twig', [
            'sessionId' => $session->getId(),
            'sessionData' => $sessionData,
        ]);
    }
}
