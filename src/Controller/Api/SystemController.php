<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/system', name: 'api_system_')]
class SystemController extends AbstractController
{
    #[Route('/resources', name: 'resources', methods: ['GET'])]
    public function getResources(Request $request): JsonResponse
    {
        try {
            // Simplified response with minimal dependencies
            $resources = [
                'memory' => [
                    'usage' => $this->getMemoryUsage(),
                    'limit' => ini_get('memory_limit'),
                ],
                'php' => [
                    'version' => PHP_VERSION,
                ],
                'server' => [
                    'time' => (new \DateTime())->format('Y-m-d H:i:s'),
                ],
            ];

            return $this->json([
                'status' => 'success',
                'resources' => $resources,
            ]);
        } catch (\Throwable $e) {
            // Log the error for troubleshooting
            error_log('API System Resources Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching system resources',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Get current memory usage formatted
    private function getMemoryUsage(): string
    {
        $memoryUsage = memory_get_usage(true);
        return $this->formatBytes($memoryUsage);
    }

    // Format bytes to human-readable format
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
        
