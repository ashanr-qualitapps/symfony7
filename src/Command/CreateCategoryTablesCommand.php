<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:create-category-tables',
    description: 'Creates Category and SubCategory tables in the database',
)]
class CreateCategoryTablesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->info('Creating migration for Category and SubCategory tables...');
        
        // Generate migration
        $process = new Process(['php', 'bin/console', 'make:migration']);
        $process->run();
        
        if (!$process->isSuccessful()) {
            $io->error('Failed to create migration: ' . $process->getErrorOutput());
            return Command::FAILURE;
        }
        
        $io->success('Migration created successfully');
        
        // Apply migration
        $io->info('Running migration to create tables...');
        $process = new Process(['php', 'bin/console', 'doctrine:migrations:migrate', '--no-interaction']);
        $process->run();
        
        if (!$process->isSuccessful()) {
            $io->error('Failed to run migration: ' . $process->getErrorOutput());
            return Command::FAILURE;
        }
        
        $io->success('Category and SubCategory tables have been created in the database');
        
        return Command::SUCCESS;
    }
}
