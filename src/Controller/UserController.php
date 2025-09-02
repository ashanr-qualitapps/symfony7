<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/users', name: 'users_list')]
    #[IsGranted('ROLE_USER')]
    public function list(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy([], ['createdAt' => 'DESC']);
        
        // Calculate statistics
        $totalUsers = count($users);
        $activeUsers = count(array_filter($users, fn($user) => $user->isActive()));
        $adminUsers = count(array_filter($users, fn($user) => $user->hasRole('ROLE_ADMIN')));
        
        // Users created this month
        $currentMonth = new \DateTime('first day of this month');
        $recentUsers = count(array_filter($users, function($user) use ($currentMonth) {
            return $user->getCreatedAt() && $user->getCreatedAt() >= $currentMonth;
        }));
        
        return $this->render('users/index.html.twig', [
            'users' => $users,
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'admin_users' => $adminUsers,
                'recent_users' => $recentUsers
            ]
        ]);
    }

    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function apiList(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();
        
        $userData = [];
        foreach ($users as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'isActive' => $user->isActive(),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        return $this->json($userData);
    }
}
