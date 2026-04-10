<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        // If the security system stored a target path (user tried to access a protected page), redirect there first
        $session = $request->getSession();
        if ($session && $session->has('_security_main')) {
            // Symfony stores the target path under _security.<firewall_name>.target_path in some versions
            $targetKey = '_security.main.target_path';
            if ($session->has($targetKey)) {
                $targetPath = $session->get($targetKey);
                if ($targetPath) {
                    return new RedirectResponse($targetPath);
                }
            }
        }

        // Determine user roles from the token
        $roles = $token->getRoleNames();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            $url = $this->urlGenerator->generate('admin_users');
        } elseif (in_array('ROLE_USER', $roles, true)) {
            $url = $this->urlGenerator->generate('app_profile');
        } else {
            $url = $this->urlGenerator->generate('app_home');
        }

        return new RedirectResponse($url);
    }
}
