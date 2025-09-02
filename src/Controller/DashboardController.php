<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Product;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Ensure user is authenticated (double-check)
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Please log in to access the dashboard.');
            return $this->redirectToRoute('app_login');
        }
        
        // Get some basic statistics for the dashboard
        $userRepository = $entityManager->getRepository(User::class);
        $productRepository = $entityManager->getRepository(Product::class);
        
        $totalUsers = $userRepository->count([]);
        $activeUsers = $userRepository->count(['isActive' => true]);
        $totalProducts = $productRepository->count([]);
        
        // Calculate low stock products (stock <= 10)
        $lowStockProducts = $entityManager->createQuery(
            'SELECT COUNT(p) FROM App\Entity\Product p WHERE p.stock <= 10'
        )->getSingleScalarResult();
        
        // Add a success flash message for successful login using the session from request
        $session = $request->getSession();
        if (!$session->has('dashboard_visited')) {
            $this->addFlash('success', 'Welcome! You have successfully logged in.');
            $session->set('dashboard_visited', true);
        }

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
                'user_role' => $user->getRoles()[0] ?? 'ROLE_USER'
            ]
        ]);
    }
}
