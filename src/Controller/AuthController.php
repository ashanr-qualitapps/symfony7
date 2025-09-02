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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Email and password are required',
                'errors' => ['email' => 'Email is required', 'password' => 'Password is required']
            ], 400);
        }

        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->isActive()) {
            return $this->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 403);
        }

        // In a real application, you would generate a JWT token here
        // For this example, we'll return user data and a mock token
        return $this->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->toArray(),
                'token' => $this->generateMockToken($user),
                'expires_in' => 3600 // 1 hour
            ]
        ]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
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
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user->toArray()
            ]
        ], 201);
    }

    #[Route('/me', name: 'api_user_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
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

    #[Route('/me', name: 'api_update_profile', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
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

        // Update allowed fields
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
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
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user->toArray()
            ]
        ]);
    }

    #[Route('/change-password', name: 'api_change_password', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
            return $this->json([
                'success' => false,
                'message' => 'Current password and new password are required'
            ], 400);
        }

        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
            return $this->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Validate new password
        if (strlen($data['newPassword']) < 8) {
            return $this->json([
                'success' => false,
                'message' => 'New password must be at least 8 characters long'
            ], 400);
        }

        // Hash and set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);

        // Save user
        $this->userRepository->save($user);

        return $this->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function logout(): JsonResponse
    {
        // In a real application with JWT, you would invalidate the token
        // For this example, we'll just return a success message
        return $this->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    private function generateMockToken(User $user): string
    {
        // In a real application, you would use a proper JWT library
        // This is just a mock token for demonstration
        return base64_encode(json_encode([
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'exp' => time() + 3600, // 1 hour expiration
            'iat' => time()
        ]));
    }
}
