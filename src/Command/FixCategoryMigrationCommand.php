<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:fix-category-migration',
    description: 'Fixes category and subcategory migration issue by creating tables in the correct order',
)]
class FixCategoryMigrationCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $io->info('Starting migration fix for category tables...');
            
            // Disable foreign key checks temporarily
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            
            // Drop tables if they exist to avoid conflicts
            $io->info('Removing existing tables if present...');
            $this->connection->executeStatement('DROP TABLE IF EXISTS product');
            $this->connection->executeStatement('DROP TABLE IF EXISTS sub_category');
            $this->connection->executeStatement('DROP TABLE IF EXISTS category');
            
            // Create tables in the correct order
            $io->info('Creating category table...');
            $this->connection->executeStatement('
                CREATE TABLE category (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ');
            
            $io->info('Creating sub_category table...');
            $this->connection->executeStatement('
                CREATE TABLE sub_category (
                    id INT AUTO_INCREMENT NOT NULL,
                    category_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description VARCHAR(255) DEFAULT NULL,
                    INDEX IDX_BCE3F79812469DE2 (category_id),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_BCE3F79812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ');
            
            $io->info('Creating product table...');
            $this->connection->executeStatement('
                CREATE TABLE product (
                    id INT AUTO_INCREMENT NOT NULL,
                    category_id INT NOT NULL,
                    sub_category_id INT DEFAULT NULL,
                    name VARCHAR(255) NOT NULL,
                    description VARCHAR(255) DEFAULT NULL,
                    price DOUBLE PRECISION NOT NULL,
                    stock INT DEFAULT NULL,
                    INDEX IDX_D34A04AD12469DE2 (category_id),
                    INDEX IDX_D34A04ADF7BFE87C (sub_category_id),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id),
                    CONSTRAINT FK_D34A04ADF7BFE87C FOREIGN KEY (sub_category_id) REFERENCES sub_category (id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            ');
            
            // Re-enable foreign key checks
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            
            // Update migration_versions table to mark our problematic migration as executed
            $io->info('Updating migration version records...');
            
            // First, check if the migration exists in the database
            $stmt = $this->connection->prepare('SELECT * FROM doctrine_migration_versions WHERE version = :version');
            $stmt->bindValue('version', 'DoctrineMigrations\\Version20250902070729');
            $result = $stmt->executeQuery();
            $exists = count($result->fetchAllAssociative()) > 0;
            
            if (!$exists) {
                $this->connection->executeStatement('
                    INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) 
                    VALUES (:version, NOW(), 1)
                ', ['version' => 'DoctrineMigrations\\Version20250902070729']);
            } else {
                $this->connection->executeStatement('
                    UPDATE doctrine_migration_versions SET executed_at = NOW() 
                    WHERE version = :version
                ', ['version' => 'DoctrineMigrations\\Version20250902070729']);
            }
            
            $io->success('Migration has been fixed! Category, SubCategory and Product tables have been created in the correct order.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            // Re-enable foreign key checks in case of error
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            return Command::FAILURE;
        }
    }
}
