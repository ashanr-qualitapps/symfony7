<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Create test users for login testing',
)]
class CreateTestUsersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create admin user
        $adminUser = $this->createUserIfNotExists(
            'admin@example.com',
            'admin',
            'password',
            ['ROLE_ADMIN', 'ROLE_USER'],
            'Admin',
            'User'
        );

        // Create regular user
        $regularUser = $this->createUserIfNotExists(
            'user@example.com',
            'user',
            'password',
            ['ROLE_USER'],
            'Regular',
            'User'
        );

        $this->entityManager->flush();

        if ($adminUser) {
            $io->success('Admin user created: admin@example.com / password');
        } else {
            $io->note('Admin user already exists: admin@example.com');
        }

        if ($regularUser) {
            $io->success('Regular user created: user@example.com / password');
        } else {
            $io->note('Regular user already exists: user@example.com');
        }

        $io->success('Test users are ready for login testing!');

        return Command::SUCCESS;
    }

    private function createUserIfNotExists(
        string $email,
        string $username,
        string $plainPassword,
        array $roles,
        string $firstName,
        string $lastName
    ): ?User {
        $userRepository = $this->entityManager->getRepository(User::class);
        
        // Check if user already exists
        $existingUser = $userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            return null;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $user->setIsActive(true);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);

        return $user;
    }
}
