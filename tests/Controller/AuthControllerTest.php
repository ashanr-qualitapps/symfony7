<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Get services from the container
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Create database schema for testing
        $this->setupDatabase();
        
        // Clean up any existing test data
        $this->cleanUpTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanUpTestData();
        parent::tearDown();
    }

    private function setupDatabase(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        // Drop and create schema
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function cleanUpTestData(): void
    {
        // Remove test users
        $testEmails = ['test@example.com', 'newuser@test.com', 'existing@test.com'];
        foreach ($testEmails as $email) {
            $user = $this->userRepository->findByEmail($email);
            if ($user) {
                $this->entityManager->remove($user);
            }
        }
        $this->entityManager->flush();
    }

    private function createTestUser(string $email = 'test@example.com', string $username = 'testuser', string $password = 'password123'): User
    {
        $user = new User();
        $user->setEmail($email)
             ->setUsername($username)
             ->setFirstName('Test')
             ->setLastName('User')
             ->setRoles(['ROLE_USER'])
             ->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    // ==================== LOGIN TESTS ====================

    public function testLoginSuccess(): void
    {
        // Create a test user
        $this->createTestUser('test@example.com', 'testuser', 'password123');

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Login successful', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('user', $data['data']);
        $this->assertArrayHasKey('token', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
        $this->assertEquals('test@example.com', $data['data']['user']['email']);
        $this->assertEquals('testuser', $data['data']['user']['username']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $this->createTestUser('test@example.com', 'testuser', 'password123');

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid credentials', $data['message']);
    }

    public function testLoginNonExistentUser(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid credentials', $data['message']);
    }

    public function testLoginMissingEmail(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'password' => 'password123'
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Email and password are required', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testLoginMissingPassword(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com'
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Email and password are required', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testLoginInactiveUser(): void
    {
        $user = $this->createTestUser('test@example.com', 'testuser', 'password123');
        $user->setIsActive(false);
        $this->entityManager->flush();

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Account is deactivated', $data['message']);
    }

    public function testLoginInvalidJson(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{invalid json}');

        $response = $this->client->getResponse();
        
        // Should handle invalid JSON gracefully
        $this->assertContains($response->getStatusCode(), [Response::HTTP_BAD_REQUEST, Response::HTTP_UNAUTHORIZED]);
    }

    // ==================== REGISTER TESTS ====================

    public function testRegisterSuccess(): void
    {
        $userData = [
            'email' => 'newuser@test.com',
            'username' => 'newuser',
            'password' => 'password123',
            'firstName' => 'New',
            'lastName' => 'User'
        ];

        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('User registered successfully', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('user', $data['data']);
        $this->assertEquals('newuser@test.com', $data['data']['user']['email']);
        $this->assertEquals('newuser', $data['data']['user']['username']);

        // Verify user was actually created in database
        $user = $this->userRepository->findByEmail('newuser@test.com');
        $this->assertNotNull($user);
        $this->assertEquals('newuser', $user->getUsername());
        $this->assertEquals('New', $user->getFirstName());
        $this->assertEquals('User', $user->getLastName());
    }

    public function testRegisterMinimumRequiredFields(): void
    {
        $userData = [
            'email' => 'minimal@test.com',
            'username' => 'minimal',
            'password' => 'password123'
        ];

        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertTrue($data['success']);
        
        // Verify user was created
        $user = $this->userRepository->findByEmail('minimal@test.com');
        $this->assertNotNull($user);
    }

    public function testRegisterDuplicateEmail(): void
    {
        // Create existing user
        $this->createTestUser('existing@test.com', 'existing', 'password123');

        $userData = [
            'email' => 'existing@test.com',
            'username' => 'newuser',
            'password' => 'password123'
        ];

        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('User with this email already exists', $data['message']);
    }

    public function testRegisterDuplicateUsername(): void
    {
        // Create existing user
        $this->createTestUser('existing@test.com', 'existing', 'password123');

        $userData = [
            'email' => 'newuser@test.com',
            'username' => 'existing',
            'password' => 'password123'
        ];

        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('User with this username already exists', $data['message']);
    }

    public function testRegisterInvalidEmail(): void
    {
        $userData = [
            'email' => 'invalid-email',
            'username' => 'testuser',
            'password' => 'password123'
        ];

        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testRegisterEmptyPassword(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => ''
        ];

        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testRegisterInvalidJson(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{invalid json}');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid JSON data', $data['message']);
    }

    public function testRegisterMissingRequiredFields(): void
    {
        $userData = [
            'firstName' => 'Test'
        ];

        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    // ==================== INTEGRATION TESTS ====================

    public function testRegisterThenLogin(): void
    {
        // First register a user
        $userData = [
            'email' => 'integration@test.com',
            'username' => 'integration',
            'password' => 'password123',
            'firstName' => 'Integration',
            'lastName' => 'Test'
        ];

        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        // Then try to login with the same credentials
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'integration@test.com',
            'password' => 'password123'
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Login successful', $data['message']);
        $this->assertEquals('integration@test.com', $data['data']['user']['email']);
    }

    public function testPasswordHashing(): void
    {
        $password = 'testpassword123';
        
        // Register a user
        $userData = [
            'email' => 'hash@test.com',
            'username' => 'hashtest',
            'password' => $password
        ];

        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($userData));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        // Verify password is hashed in database
        $user = $this->userRepository->findByEmail('hash@test.com');
        $this->assertNotNull($user);
        $this->assertNotEquals($password, $user->getPassword());
        $this->assertTrue($this->passwordHasher->isPasswordValid($user, $password));
    }
}
