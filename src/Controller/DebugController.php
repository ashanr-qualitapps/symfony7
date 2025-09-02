<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

class DebugController extends AbstractController
{
    #[Route('/debug/users', name: 'app_debug_users')]
    public function users(EntityManagerInterface $entityManager): Response
    {
        // This route is for debugging only - remove in production
        $userClass = $this->getParameter('security.user.provider.entity.class');
        
        if (!$userClass) {
            $userClass = 'App\Entity\User';
        }
        
        try {
            $users = $entityManager->getRepository($userClass)->findAll();
            $userData = [];
            
            foreach ($users as $user) {
                $data = [
                    'email' => method_exists($user, 'getEmail') ? $user->getEmail() : 'N/A',
                    'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
                ];
                $userData[] = $data;
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Found ' . count($users) . ' users',
                'users' => $userData
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/debug/config', name: 'app_debug_config')]
    public function config(): Response
    {
        // This route is for debugging only - remove in production
        return $this->json([
            'success' => true,
            'message' => 'Configuration check',
            'login_path' => $this->generateUrl('app_login'),
            'dashboard_path' => $this->generateUrl('app_dashboard'),
            'current_user' => $this->getUser() ? $this->getUser()->getUserIdentifier() : 'Anonymous'
        ]);
    }
}
