<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'health_check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        $data = [
            'status' => 'OK',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'environment' => $this->getParameter('kernel.environment'),
            'php_version' => PHP_VERSION,
            'symfony_version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'uptime' => $this->getUptime()
        ];

        return $this->json($data);
    }

    #[Route('/api/ping', name: 'ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            'message' => 'pong',
            'timestamp' => date('c')
        ]);
    }

    #[Route('/api/status', name: 'status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        // You can add more sophisticated health checks here
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'filesystem' => $this->checkFilesystem()
        ];

        $allHealthy = array_reduce($checks, function($carry, $check) {
            return $carry && $check['status'] === 'healthy';
        }, true);

        return $this->json([
            'overall_status' => $allHealthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => date('c')
        ], $allHealthy ? 200 : 503);
    }

    private function formatBytes(int $size, int $precision = 2): string
    {
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    private function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = shell_exec('uptime');
            if ($uptime) {
                return trim($uptime);
            }
        }
        
        return 'N/A';
    }

    private function checkDatabase(): array
    {
        // Since no database is configured yet, we'll return a placeholder
        return [
            'status' => 'not_configured',
            'message' => 'Database not configured'
        ];
    }

    private function checkCache(): array
    {
        try {
            $cacheDir = $this->getParameter('kernel.cache_dir');
            $writable = is_writable($cacheDir);
            
            return [
                'status' => $writable ? 'healthy' : 'unhealthy',
                'message' => $writable ? 'Cache directory is writable' : 'Cache directory is not writable',
                'cache_dir' => $cacheDir
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache check failed: ' . $e->getMessage()
            ];
        }
    }

    private function checkFilesystem(): array
    {
        try {
            $projectDir = $this->getParameter('kernel.project_dir');
            $varDir = $projectDir . '/var';
            
            $checks = [
                'var_directory_exists' => is_dir($varDir),
                'var_directory_writable' => is_writable($varDir),
                'log_directory_writable' => is_writable($varDir . '/log'),
                'cache_directory_writable' => is_writable($varDir . '/cache')
            ];
            
            $allGood = array_reduce($checks, function($carry, $check) {
                return $carry && $check;
            }, true);
            
            return [
                'status' => $allGood ? 'healthy' : 'unhealthy',
                'details' => $checks
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Filesystem check failed: ' . $e->getMessage()
            ];
        }
    }
}
