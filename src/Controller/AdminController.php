<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/users', name: 'api_admin_users_list', methods: ['GET'])]
    public function listUsers(Request $request): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        // Filter by role if specified
        $role = $request->query->get('role');
        if ($role) {
            $users = array_filter($users, function ($user) use ($role) {
                return $user->hasRole($role);
            });
        }

        // Filter by active status if specified
        $active = $request->query->get('active');
        if ($active !== null) {
            $isActive = filter_var($active, FILTER_VALIDATE_BOOLEAN);
            $users = array_filter($users, function ($user) use ($isActive) {
                return $user->isActive() === $isActive;
            });
        }

        // Search by email or username
        $search = $request->query->get('search');
        if ($search) {
            $users = array_filter($users, function ($user) use ($search) {
                return stripos($user->getEmail(), $search) !== false ||
                       stripos($user->getUsername(), $search) !== false ||
                       stripos($user->getFullName(), $search) !== false;
            });
        }

        // Convert to array and re-index
        $usersData = array_map(function ($user) {
            return $user->toArray();
        }, array_values($users));

        return $this->json([
            'success' => true,
            'data' => [
                'users' => $usersData,
                'total' => count($usersData)
            ]
        ]);
    }

    #[Route('/users/{id}', name: 'api_admin_user_get', methods: ['GET'])]
    public function getUserById(int $id): JsonResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'user' => $user->toArray()
            ]
        ]);
    }

    #[Route('/users', name: 'api_admin_user_create', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON data'
            ], 400);
        }

        // Check if user already exists
        if (isset($data['email']) && $this->userRepository->existsByEmail($data['email'])) {
            return $this->json([
                'success' => false,
                'message' => 'User with this email already exists'
            ], 409);
        }

        if (isset($data['username']) && $this->userRepository->existsByUsername($data['username'])) {
            return $this->json([
                'success' => false,
                'message' => 'User with this username already exists'
            ], 409);
        }

        // Create new user
        $user = new User();
        $user->setEmail($data['email'] ?? null)
             ->setUsername($data['username'] ?? null)
             ->setFirstName($data['firstName'] ?? null)
             ->setLastName($data['lastName'] ?? null);

        // Set roles if provided
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        // Set active status if provided
        if (isset($data['isActive'])) {
            $user->setIsActive((bool) $data['isActive']);
        }

        // Hash password
        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        // Validate user
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], 422);
        }

        // Save user
        $this->userRepository->save($user);

        return $this->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'user' => $user->toArray()
            ]
        ], 201);
    }

    #[Route('/users/{id}', name: 'api_admin_user_update', methods: ['PUT', 'PATCH'])]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON data'
            ], 400);
        }

        // Update fields
        if (isset($data['email'])) {
            // Check if email is already taken by another user
            $existingUser = $this->userRepository->findByEmail($data['email']);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Email is already taken'
                ], 409);
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['username'])) {
            // Check if username is already taken by another user
            $existingUser = $this->userRepository->findByUsername($data['username']);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Username is already taken'
                ], 409);
            }
            $user->setUsername($data['username']);
        }

        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }

        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        if (isset($data['isActive'])) {
            $user->setIsActive((bool) $data['isActive']);
        }

        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        // Validate user
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], 422);
        }

        // Save user
        $this->userRepository->save($user);

        return $this->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => [
                'user' => $user->toArray()
            ]
        ]);
    }

    #[Route('/users/{id}', name: 'api_admin_user_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent admin from deleting themselves
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $this->userRepository->delete($user);

        return $this->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    #[Route('/users/{id}/activate', name: 'api_admin_user_activate', methods: ['POST'])]
    public function activateUser(int $id): JsonResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->setIsActive(true);
        $this->userRepository->save($user);

        return $this->json([
            'success' => true,
            'message' => 'User activated successfully',
            'data' => [
                'user' => $user->toArray()
            ]
        ]);
    }

    #[Route('/users/{id}/deactivate', name: 'api_admin_user_deactivate', methods: ['POST'])]
    public function deactivateUser(int $id): JsonResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent admin from deactivating themselves
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account'
            ], 403);
        }

        $user->setIsActive(false);
        $this->userRepository->save($user);

        return $this->json([
            'success' => true,
            'message' => 'User deactivated successfully',
            'data' => [
                'user' => $user->toArray()
            ]
        ]);
    }

    #[Route('/users/{id}/roles', name: 'api_admin_user_update_roles', methods: ['PUT'])]
    public function updateUserRoles(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['roles']) || !is_array($data['roles'])) {
            return $this->json([
                'success' => false,
                'message' => 'Roles array is required'
            ], 400);
        }

        $user->setRoles($data['roles']);
        $this->userRepository->save($user);

        return $this->json([
            'success' => true,
            'message' => 'User roles updated successfully',
            'data' => [
                'user' => $user->toArray()
            ]
        ]);
    }

    #[Route('/stats', name: 'api_admin_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        $stats = [
            'total_users' => count($users),
            'active_users' => count(array_filter($users, fn($u) => $u->isActive())),
            'inactive_users' => count(array_filter($users, fn($u) => !$u->isActive())),
            'admin_users' => count(array_filter($users, fn($u) => $u->hasRole('ROLE_ADMIN'))),
            'regular_users' => count(array_filter($users, fn($u) => !$u->hasRole('ROLE_ADMIN'))),
        ];

        return $this->json([
            'success' => true,
            'data' => [
                'stats' => $stats
            ]
        ]);
    }
}
