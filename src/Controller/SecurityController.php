<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils, 
        Request $request,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager = null
    ): Response {
        // Debug information
        $debugInfo = [];
        $debugInfo['request_method'] = $request->getMethod();
        $debugInfo['is_xhr'] = $request->isXmlHttpRequest() ? 'Yes' : 'No';
        
        // Check if form was submitted
        if ($request->isMethod('POST')) {
            $username = $request->request->get('_username');
            $password = $request->request->get('_password');
            
            $logger->info('Login form submitted', [
                'username' => $username,
                'remember_me' => $request->request->has('_remember_me') ? 'Yes' : 'No',
                'has_password' => !empty($password) ? 'Yes' : 'No',
                'password_length' => strlen($password ?? ''),
            ]);
            
            $debugInfo['form_submitted'] = true;
            $debugInfo['username_provided'] = $username;
            $debugInfo['password_provided'] = !empty($password) ? 'Yes' : 'No';
            
            // Try to find the user
            if ($entityManager) {
                try {
                    $userRepository = $entityManager->getRepository('App\Entity\User');
                    $user = $userRepository->findOneBy(['email' => $username]);
                    
                    if ($user) {
                        $debugInfo['user_exists'] = true;
                        $debugInfo['user_roles'] = $user->getRoles();
                        $debugInfo['user_active'] = $user->isActive();
                        $debugInfo['user_id'] = $user->getId();
                        
                        $logger->info('User found in database', [
                            'user_id' => $user->getId(),
                            'email' => $user->getEmail(),
                            'is_active' => $user->isActive(),
                            'roles' => $user->getRoles()
                        ]);
                    } else {
                        $debugInfo['user_exists'] = false;
                        $logger->warning('Login attempt with non-existent username', [
                            'username' => $username
                        ]);
                    }
                } catch (\Exception $e) {
                    $debugInfo['error'] = 'Exception: ' . $e->getMessage();
                    $logger->error('Exception during user lookup: ' . $e->getMessage());
                }
            }
        }
        
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error) {
            $logger->warning('Authentication error', [
                'error' => $error->getMessage(),
                'error_type' => get_class($error)
            ]);
            
            $debugInfo['auth_error'] = $error->getMessage();
            $debugInfo['auth_error_type'] = get_class($error);
        }
        
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // If the user is already logged in, redirect to dashboard
        if ($this->getUser()) {
            $logger->info('User already logged in, redirecting to dashboard', [
                'user' => $this->getUser()->getUserIdentifier()
            ]);
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'debug_mode' => true,
            'debug_info' => $debugInfo
        ]);
    }

    #[Route('/login-check', name: 'app_login_check')]
    public function loginCheck(Request $request, LoggerInterface $logger): Response
    {
        // This is a debug endpoint to check login form submission
        $logger->info('Login check endpoint hit');
        
        $formData = [
            'username' => $request->request->get('_username'),
            'password_provided' => !empty($request->request->get('_password')),
            'csrf_token' => $request->request->get('_csrf_token'),
            'request_method' => $request->getMethod(),
            'current_user' => $this->getUser() ? $this->getUser()->getUserIdentifier() : 'Anonymous'
        ];
        
        // Redirect to the dashboard if login was successful
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }
        
        return $this->json([
            'message' => 'Login check',
            'form_data' => $formData,
            'authenticated' => $this->getUser() !== null
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key in the firewall
        throw new \LogicException('This method should never be reached!');
    }
}
