<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Psr\Log\LoggerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private LoggerInterface $logger;

    public function __construct(UrlGeneratorInterface $urlGenerator, LoggerInterface $logger)
    {
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        $session = $request->getSession();

        // Log successful login
        $this->logger->info('User successfully authenticated', [
            'user' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent')
        ]);

        // Clear any previous dashboard visit flag to show welcome message
        $session->remove('dashboard_visited');

        // Add success flash message
        if ($session instanceof SessionInterface) {
            $session->set('_flash_messages_success', [
                sprintf('Welcome back, %s! Login successful.', $user->getUserIdentifier())
            ]);
        }

        // Determine redirect URL based on user role or intended target
        $targetPath = $this->getTargetPath($request, $user);

        return new RedirectResponse($targetPath);
    }

    private function getTargetPath(Request $request, $user): string
    {
        // Check if there's a target path in the request
        $targetPath = $request->request->get('_target_path');
        
        if ($targetPath) {
            return $targetPath;
        }

        // Check for referer-based redirect
        $referer = $request->headers->get('referer');
        if ($referer && !str_contains($referer, '/login')) {
            return $referer;
        }

        // Default redirect based on user roles
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            // Admins might have a special admin dashboard in the future
            return $this->urlGenerator->generate('app_dashboard');
        }

        // Default to dashboard for all authenticated users
        return $this->urlGenerator->generate('app_dashboard');
    }
}
