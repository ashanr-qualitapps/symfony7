<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DatabaseTestController extends AbstractController
{
    #[Route('/test-db', name: 'test_database')]
    public function testConnection(Connection $connection): Response
    {
        try {
            $connection->connect();
            return new Response('Database connection successful!');
        } catch (\Exception $e) {
            return new Response('Database connection failed: ' . $e->getMessage(), 500);
        }
    }
}
