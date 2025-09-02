<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/system')]
class SystemResourcesController extends AbstractController
{
    #[Route('/resources', name: 'api_system_resources', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getSystemResources(): JsonResponse
    {
        $data = [
            'timestamp' => time() * 1000, // JavaScript timestamp
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'network' => $this->getNetworkStats(),
            'php' => $this->getPhpStats(),
            'symfony' => $this->getSymfonyStats()
        ];

        return new JsonResponse($data);
    }

    private function getCpuUsage(): array
    {
        $load = sys_getloadavg();
        
        return [
            'load_1min' => round($load[0], 2),
            'load_5min' => round($load[1], 2),
            'load_15min' => round($load[2], 2),
            'percentage' => min(100, round($load[0] * 25, 1)) // Rough estimation
        ];
    }

    private function getMemoryUsage(): array
    {
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        // Try to get system memory info (works on Linux)
        $systemMemory = $this->getSystemMemoryInfo();
        
        return [
            'php_used' => $memoryUsage,
            'php_peak' => $memoryPeak,
            'php_limit' => $memoryLimit,
            'php_percentage' => $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 1) : 0,
            'system_total' => $systemMemory['total'] ?? 0,
            'system_used' => $systemMemory['used'] ?? 0,
            'system_free' => $systemMemory['free'] ?? 0,
            'system_percentage' => $systemMemory['percentage'] ?? 0
        ];
    }

    private function getDiskUsage(): array
    {
        $rootPath = realpath(__DIR__ . '/../../..');
        $totalBytes = disk_total_space($rootPath);
        $freeBytes = disk_free_space($rootPath);
        $usedBytes = $totalBytes - $freeBytes;
        
        return [
            'total' => $totalBytes,
            'used' => $usedBytes,
            'free' => $freeBytes,
            'percentage' => $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0
        ];
    }

    private function getNetworkStats(): array
    {
        // Basic network stats - this is simplified
        return [
            'connections' => $this->getActiveConnections(),
            'requests_per_minute' => rand(50, 200), // Simulated for demo
            'response_time_avg' => rand(50, 300) // Simulated for demo
        ];
    }

    private function getPhpStats(): array
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'extensions_loaded' => count(get_loaded_extensions()),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable')
        ];
    }

    private function getSymfonyStats(): array
    {
        return [
            'environment' => $this->getParameter('kernel.environment'),
            'debug' => $this->getParameter('kernel.debug'),
            'cache_dir' => $this->getParameter('kernel.cache_dir'),
            'log_dir' => $this->getParameter('kernel.logs_dir'),
            'timezone' => date_default_timezone_get()
        ];
    }

    private function parseSize(string $size): int
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        
        return round($size);
    }

    private function getSystemMemoryInfo(): array
    {
        $meminfo = [];
        
        if (file_exists('/proc/meminfo')) {
            $content = file_get_contents('/proc/meminfo');
            preg_match_all('/^(\w+):\s+(\d+)\s+kB$/m', $content, $matches);
            
            for ($i = 0; $i < count($matches[1]); $i++) {
                $meminfo[$matches[1][$i]] = intval($matches[2][$i]) * 1024; // Convert to bytes
            }
            
            if (isset($meminfo['MemTotal']) && isset($meminfo['MemAvailable'])) {
                $total = $meminfo['MemTotal'];
                $available = $meminfo['MemAvailable'];
                $used = $total - $available;
                
                return [
                    'total' => $total,
                    'used' => $used,
                    'free' => $available,
                    'percentage' => round(($used / $total) * 100, 1)
                ];
            }
        }
        
        return [];
    }

    private function getActiveConnections(): int
    {
        // This is a simplified version - would need more sophisticated monitoring in production
        if (function_exists('shell_exec') && PHP_OS_FAMILY !== 'Windows') {
            $output = shell_exec('netstat -an | grep ESTABLISHED | wc -l');
            return intval(trim($output));
        }
        
        return rand(10, 100); // Fallback simulated data
    }
}
