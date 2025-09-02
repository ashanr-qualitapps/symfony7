<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserProviderInterface, PasswordUpgraderInterface
{
    private ?LoggerInterface $logger = null;
    private bool $debugMode = true; // Enable debug mode

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger = null)
    {
        parent::__construct($registry, User::class);
        $this->logger = $logger;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->findOneBy(['email' => $identifier]);
        
        if (!$user) {
            throw new \Symfony\Component\Security\Core\Exception\UserNotFoundException(
                sprintf('User with email "%s" not found.', $identifier)
            );
        }

        return $user;
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->save($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    /**
     * Check if a user with the given email exists
     */
    public function existsByEmail(string $email): bool
    {
        return $this->findOneBy(['email' => $email]) !== null;
    }

    /**
     * Check if a user with the given username exists
     */
    public function existsByUsername(string $username): bool
    {
        return $this->findOneBy(['username' => $username]) !== null;
    }

    /**
     * Save a user entity to the database
     */
    public function save(User $user, bool $flush = true): void
    {
        try {
            $this->debug('Starting user save operation', [
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'has_id' => $user->getId() ? 'yes' : 'no'
            ]);
            
            // Check if email exists but with a different user
            if (!$user->getId() && $this->existsByEmail($user->getEmail())) {
                $errorMsg = sprintf('User with email "%s" already exists.', $user->getEmail());
                $this->debug($errorMsg, [], 'error');
                throw new \RuntimeException($errorMsg);
            }
            
            // Convert DateTimeImmutable to DateTime if needed
            if ($user->getCreatedAt() instanceof \DateTimeImmutable) {
                $this->debug('Converting DateTimeImmutable to DateTime for createdAt');
                $user->setCreatedAt(new \DateTime($user->getCreatedAt()->format('Y-m-d H:i:s')));
            }
            
            // Set timestamps if needed
            if (!$user->getUpdatedAt()) {
                $this->debug('Setting missing updatedAt timestamp');
                $user->setUpdatedAt(new \DateTime());
            }
            
            $entityManager = $this->getEntityManager();
            
            $this->debug('Persisting user to entity manager');
            $entityManager->persist($user);
            
            if ($flush) {
                $this->debug('Flushing entity manager');
                $entityManager->flush();
                
                $this->debug('User saved successfully', [
                    'id' => $user->getId(),
                    'email' => $user->getEmail()
                ]);
            }
        } catch (\Exception $e) {
            $this->debug('Error saving user: ' . $e->getMessage(), [
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ], 'error');
            
            throw $e;
        }
    }

    /**
     * Debug helper method to log messages consistently
     */
    private function debug(string $message, array $context = [], string $level = 'info'): void
    {
        if (!$this->debugMode) {
            return;
        }
        
        // Always echo to PHP error log for visibility
        error_log('[UserRepository] ' . $message . ' ' . json_encode($context));
        
        if ($this->logger) {
            switch ($level) {
                case 'error':
                    $this->logger->error($message, $context);
                    break;
                case 'warning':
                    $this->logger->warning($message, $context);
                    break;
                case 'debug':
                    $this->logger->debug($message, $context);
                    break;
                default:
                    $this->logger->info($message, $context);
                    break;
            }
        }
    }
    
    /**
     * Special method for registration debugging
     */
    public function debugRegistration(User $user): array
    {
        $entityManager = $this->getEntityManager();
        $connection = $entityManager->getConnection();
        $diagnostics = [];
        
        // Check database connection
        try {
            $connection->connect();
            $diagnostics['database_connection'] = 'OK';
        } catch (\Exception $e) {
            $diagnostics['database_connection'] = 'FAILED: ' . $e->getMessage();
        }
        
        // Check if user exists by email - use findOneBy instead of existsByEmail
        $diagnostics['user_exists_by_email'] = $this->findOneBy(['email' => $user->getEmail()]) ? 'YES' : 'NO';
        
        // Check if user exists by username - use findOneBy instead of existsByUsername
        $diagnostics['user_exists_by_username'] = $this->findOneBy(['username' => $user->getUsername()]) ? 'YES' : 'NO';
        
        // Check user data
        $diagnostics['user_data'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'has_password' => !empty($user->getPassword()) ? 'YES' : 'NO',
            'roles' => $user->getRoles(),
            'created_at' => $user->getCreatedAt() ? $user->getCreatedAt()->format('c') : 'NULL',
            'updated_at' => $user->getUpdatedAt() ? $user->getUpdatedAt()->format('c') : 'NULL',
            'created_at_class' => $user->getCreatedAt() ? get_class($user->getCreatedAt()) : 'NULL',
            'updated_at_class' => $user->getUpdatedAt() ? get_class($user->getUpdatedAt()) : 'NULL',
        ];
        
        $this->debug('Registration diagnostics', $diagnostics);
        
        return $diagnostics;
    }

    /**
     * Remove a user entity from the database
     */
    public function remove(User $user, bool $flush = true): void
    {
        $this->debug('Removing user', [
            'id' => $user->getId(),
            'email' => $user->getEmail()
        ]);
        
        $entityManager = $this->getEntityManager();
        $entityManager->remove($user);
        
        if ($flush) {
            $entityManager->flush();
            $this->debug('User removed successfully');
        }
    }

    /**
     * Create default users if none exist
     */
    public function createDefaultUsers(): void
    {
        if ($this->count([]) > 0) {
            return;
        }
        
        // Admin user
        $admin = new User();
        $admin->setEmail('admin@example.com')
              ->setUsername('admin')
              ->setFirstName('Admin')
              ->setLastName('User')
              ->setRoles(['ROLE_ADMIN'])
              ->setPassword('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') // password: password
              ->setUpdatedAt(new \DateTime());

        // Regular user
        $user = new User();
        $user->setEmail('user@example.com')
             ->setUsername('user')
             ->setFirstName('Regular')
             ->setLastName('User')
             ->setRoles(['ROLE_USER'])
             ->setPassword('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') // password: password
             ->setUpdatedAt(new \DateTime());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($admin);
        $entityManager->persist($user);
        
        try {
            $entityManager->flush();
            
            if ($this->logger) {
                $this->logger->info('Created default users');
            }
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error creating default users', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}

