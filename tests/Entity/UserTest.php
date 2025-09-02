<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testUserCreation(): void
    {
        $user = new User();
        $user->setEmail('test@example.com')
             ->setUsername('testuser')
             ->setFirstName('Test')
             ->setLastName('User')
             ->setPassword('hashedpassword');

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('Test', $user->getFirstName());
        $this->assertEquals('User', $user->getLastName());
        $this->assertEquals('hashedpassword', $user->getPassword());
        $this->assertTrue($user->isActive());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testUserDefaults(): void
    {
        $user = new User();
        
        $this->assertTrue($user->isActive());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertInstanceOf(\DateTime::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $user->getUpdatedAt());
    }

    public function testUserIdentifier(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        
        $this->assertEquals('testuser', $user->getUserIdentifier());
    }

    public function testUserRoles(): void
    {
        $user = new User();
        
        // Test default role
        $this->assertContains('ROLE_USER', $user->getRoles());
        
        // Test setting admin role
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testUserActivation(): void
    {
        $user = new User();
        
        // Test default state
        $this->assertTrue($user->isActive());
        
        // Test deactivation
        $user->setIsActive(false);
        $this->assertFalse($user->isActive());
        
        // Test reactivation
        $user->setIsActive(true);
        $this->assertTrue($user->isActive());
    }

    public function testUserToArray(): void
    {
        $user = new User();
        $user->setId(1)
             ->setEmail('test@example.com')
             ->setUsername('testuser')
             ->setFirstName('Test')
             ->setLastName('User')
             ->setRoles(['ROLE_ADMIN', 'ROLE_USER'])
             ->setIsActive(true);

        $array = $user->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('Test', $array['firstName']);
        $this->assertEquals('User', $array['lastName']);
        $this->assertEquals(['ROLE_ADMIN', 'ROLE_USER'], $array['roles']);
        $this->assertTrue($array['isActive']);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayHasKey('updatedAt', $array);
    }

    public function testEmailValidation(): void
    {
        $user = new User();
        $user->setUsername('testuser')
             ->setPassword('password');

        // Test invalid email
        $user->setEmail('invalid-email');
        $violations = $this->validator->validate($user);
        $this->assertGreaterThan(0, count($violations));

        // Test valid email
        $user->setEmail('valid@example.com');
        $violations = $this->validator->validate($user);
        
        // Check if email validation passes (there might be other validation errors)
        $emailErrors = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'email') {
                $emailErrors[] = $violation;
            }
        }
        $this->assertEmpty($emailErrors);
    }

    public function testUsernameRequirement(): void
    {
        $user = new User();
        $user->setEmail('test@example.com')
             ->setPassword('password');

        // Username is required, so validation should fail
        $violations = $this->validator->validate($user);
        
        $usernameErrors = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'username') {
                $usernameErrors[] = $violation;
            }
        }
        
        $this->assertNotEmpty($usernameErrors);
    }

    public function testPasswordRequirement(): void
    {
        $user = new User();
        $user->setEmail('test@example.com')
             ->setUsername('testuser');

        // Password is required, so validation should fail
        $violations = $this->validator->validate($user);
        
        $passwordErrors = [];
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'password') {
                $passwordErrors[] = $violation;
            }
        }
        
        $this->assertNotEmpty($passwordErrors);
    }

    public function testTimestampUpdates(): void
    {
        $user = new User();
        $createdAt = $user->getCreatedAt();
        $updatedAt = $user->getUpdatedAt();
        
        $this->assertInstanceOf(\DateTime::class, $createdAt);
        $this->assertInstanceOf(\DateTime::class, $updatedAt);
        
        // Test that timestamps are close to current time (within 1 second)
        $now = new \DateTime();
        $this->assertLessThanOrEqual(1, abs($now->getTimestamp() - $createdAt->getTimestamp()));
        $this->assertLessThanOrEqual(1, abs($now->getTimestamp() - $updatedAt->getTimestamp()));
    }
}
