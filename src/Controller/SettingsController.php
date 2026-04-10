<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'app_settings', methods: ['GET','POST'])]
    public function settings(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $session = $request->getSession();

        $prefs = [
            'locale' => $session->get('prefs_locale', 'en'),
            'timezone' => $session->get('prefs_timezone', 'UTC'),
            'email_notifications' => $session->get('prefs_email_notifications', true),
        ];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('settings_edit', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_settings');
            }

            $prefs['locale'] = $request->request->get('locale', 'en');
            $prefs['timezone'] = $request->request->get('timezone', 'UTC');
            $prefs['email_notifications'] = $request->request->get('email_notifications') ? true : false;

            $session->set('prefs_locale', $prefs['locale']);
            $session->set('prefs_timezone', $prefs['timezone']);
            $session->set('prefs_email_notifications', $prefs['email_notifications']);

            $this->addFlash('success', 'Settings saved.');

            return $this->redirectToRoute('app_settings');
        }

        return $this->render('Front/settings.html.twig', [
            'prefs' => $prefs,
        ]);
    }
}

