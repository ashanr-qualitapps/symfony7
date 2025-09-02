<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/test')]
#[IsGranted('ROLE_ADMIN')]
class TestRunnerController extends AbstractController
{
    #[Route('/', name: 'test_runner_index')]
    public function index(): Response
    {
        return $this->render('test_runner/index.html.twig');
    }

    #[Route('/run/{suite}', name: 'test_runner_run', methods: ['POST'])]
    public function runTests(string $suite): JsonResponse
    {
        $projectRoot = $this->getParameter('kernel.project_dir');
        
        // Get PHP executable path
        $phpExecutable = $this->getPhpExecutable();
        
        $commands = [
            'product-entity' => [$phpExecutable, 'vendor/bin/phpunit', 'tests/Entity/ProductTest.php', '--testdox'],
            'product-repository' => [$phpExecutable, 'vendor/bin/phpunit', 'tests/Repository/ProductRepositoryTest.php', '--testdox'],
            'product-controller' => [$phpExecutable, 'vendor/bin/phpunit', 'tests/Controller/ProductControllerTest.php', '--testdox'],
            'product-all' => [$phpExecutable, 'vendor/bin/phpunit', 'tests/Entity/ProductTest.php', 'tests/Repository/ProductRepositoryTest.php', 'tests/Controller/ProductControllerTest.php', '--testdox'],
            'all-tests' => [$phpExecutable, 'vendor/bin/phpunit', '--testdox'],
            'user-tests' => [$phpExecutable, 'vendor/bin/phpunit', 'tests/Entity/UserTest.php', 'tests/Controller/AuthControllerTest.php', '--testdox']
        ];

        if (!isset($commands[$suite])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid test suite specified'
            ], 400);
        }

        try {
            $process = new Process($commands[$suite], $projectRoot);
            $process->setTimeout(300); // 5 minutes timeout
            
            // Set environment variables explicitly for the test process
            $env = [
                'APP_ENV' => 'test',
                'KERNEL_CLASS' => 'App\\Kernel',
            ];
            
            // Enhanced database configuration for repository tests
            if (strpos($suite, 'repository') !== false || $suite === 'product-all' || $suite === 'all-tests') {
                $env['DATABASE_URL'] = 'sqlite:///%kernel.project_dir%/var/test.db';
                $env['SYMFONY_DEPRECATIONS_HELPER'] = 'disabled';
                $env['BOOTSTRAP_CLEAR_CACHE_ENV'] = 'test';
                $env['DATABASE_MEMORY'] = 'true';
            } else {
                $env['DATABASE_URL'] = 'sqlite:///:memory:';
            }
            
            $process->setEnv($env);
            
            $process->run();

            $exitCode = $process->getExitCode();
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            return new JsonResponse([
                'success' => $exitCode === 0,
                'exitCode' => $exitCode,
                'output' => $output,
                'error' => $errorOutput,
                'suite' => $suite,
                'command' => implode(' ', $commands[$suite]),
                'working_directory' => $projectRoot,
                'php_executable' => $phpExecutable,
                'env_variables' => $env,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (ProcessFailedException $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to execute test command: ' . $exception->getMessage(),
                'suite' => $suite,
                'timestamp' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    #[Route('/debug', name: 'test_runner_debug')]
    public function debug(): JsonResponse
    {
        $projectRoot = $this->getParameter('kernel.project_dir');
        $phpExecutable = $this->getPhpExecutable();
        
        $debugInfo = [
            'project_root' => $projectRoot,
            'php_executable' => $phpExecutable,
            'php_binary_constant' => defined('PHP_BINARY') ? PHP_BINARY : 'Not defined',
            'phpunit_file_exists' => file_exists($projectRoot . '/vendor/bin/phpunit'),
            'phpunit_bat_exists' => file_exists($projectRoot . '/vendor/bin/phpunit.bat'),
            'php_version' => PHP_VERSION,
            'php_os_family' => PHP_OS_FAMILY,
            'current_user' => get_current_user(),
            'working_directory' => getcwd(),
            'app_env' => $_ENV['APP_ENV'] ?? 'not set',
            'server_app_env' => $_SERVER['APP_ENV'] ?? 'not set',
        ];
        
        // Test actual repository test command
        try {
            $command = [$phpExecutable, 'vendor/bin/phpunit', 'tests/Repository/ProductRepositoryTest.php', '--testdox'];
            $process = new Process($command, $projectRoot);
            $process->setEnv([
                'APP_ENV' => 'test',
                'KERNEL_CLASS' => 'App\\Kernel'
            ]);
            $process->setTimeout(30);
            $process->run();
            
            $debugInfo['repository_test_command'] = implode(' ', $command);
            $debugInfo['repository_test_exit_code'] = $process->getExitCode();
            $debugInfo['repository_test_output'] = trim($process->getOutput());
            $debugInfo['repository_test_error'] = trim($process->getErrorOutput());
        } catch (\Exception $e) {
            $debugInfo['repository_test_exception'] = $e->getMessage();
        }
        
        return new JsonResponse($debugInfo, 200, [], JSON_PRETTY_PRINT);
    }

    #[Route('/status', name: 'test_runner_status')]
    public function getStatus(): JsonResponse
    {
        $projectRoot = $this->getParameter('kernel.project_dir');
        
        // Get test file counts
        $testStats = [
            'entity_tests' => $this->countTestFiles($projectRoot . '/tests/Entity'),
            'controller_tests' => $this->countTestFiles($projectRoot . '/tests/Controller'),
            'repository_tests' => $this->countTestFiles($projectRoot . '/tests/Repository'),
            'total_tests' => 0
        ];
        
        $testStats['total_tests'] = $testStats['entity_tests'] + $testStats['controller_tests'] + $testStats['repository_tests'];

        // Check if PHPUnit is available - simplified approach
        $phpunitAvailable = false;
        $phpunitVersion = 'Not found';
        
        // Get PHP executable path
        $phpExecutable = $this->getPhpExecutable();
        
        try {
            // Try the most likely path first
            $process = new Process([$phpExecutable, 'vendor/bin/phpunit', '--version'], $projectRoot);
            $process->setTimeout(10);
            $process->run();
            
            if ($process->getExitCode() === 0) {
                $phpunitAvailable = true;
                $phpunitVersion = trim($process->getOutput());
            } else {
                // Fallback: check if file exists even if command failed
                $phpunitFile = $projectRoot . '/vendor/bin/phpunit';
                if (file_exists($phpunitFile)) {
                    $phpunitAvailable = true;
                    $phpunitVersion = 'PHPUnit executable found';
                    
                    // Try to get version by reading the file content
                    try {
                        $content = file_get_contents($phpunitFile);
                        if (preg_match('/PHPUnit\s+(\d+\.\d+\.\d+)/', $content, $matches)) {
                            $phpunitVersion = 'PHPUnit ' . $matches[1];
                        }
                    } catch (\Exception $e) {
                        // Ignore version extraction errors
                    }
                }
            }
        } catch (\Exception $e) {
            // If Process fails, check if file exists
            $phpunitFile = $projectRoot . '/vendor/bin/phpunit';
            if (file_exists($phpunitFile)) {
                $phpunitAvailable = true;
                $phpunitVersion = 'PHPUnit executable found (process error: ' . $e->getMessage() . ')';
            }
        }
        
        return new JsonResponse([
            'phpunit_available' => $phpunitAvailable,
            'phpunit_version' => $phpunitVersion,
            'test_stats' => $testStats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/coverage/{suite}', name: 'test_runner_coverage_view', methods: ['GET'])]
    public function viewCoverage(string $suite): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $projectRoot = $this->getParameter('kernel.project_dir');
        $coverageDir = $projectRoot . '/public/coverage/' . $suite;
        $coverageIndexFile = $coverageDir . '/index.html';
        
        $coverageAvailable = file_exists($coverageIndexFile);
        $coverageUrl = null;
        
        if ($coverageAvailable) {
            // Create a relative URL for the coverage report
            $coverageUrl = '/coverage/' . $suite . '/index.html';
        }
        
        return $this->render('test_runner/coverage.html.twig', [
            'suite' => $suite,
            'coverage_available' => $coverageAvailable,
            'coverage_url' => $coverageUrl,
        ]);
    }

    #[Route('/coverage/{suite}/generate', name: 'test_runner_coverage', methods: ['POST'])]
    public function runCoverage(string $suite): JsonResponse
    {
        $projectRoot = $this->getParameter('kernel.project_dir');
        
        // Get PHP executable path
        $phpExecutable = $this->getPhpExecutable();
        
        $coverageCommands = [
            'product-all' => [
                $phpExecutable, 'vendor/bin/phpunit',
                '--coverage-html', 'public/coverage/product',
                '--coverage-text',
                'tests/Entity/ProductTest.php',
                'tests/Repository/ProductRepositoryTest.php',
                'tests/Controller/ProductControllerTest.php'
            ]
        ];

        if (!isset($coverageCommands[$suite])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Coverage not available for this test suite'
            ], 400);
        }

        try {
            // Create coverage directory if it doesn't exist
            $coverageDir = $projectRoot . '/public/coverage';
            if (!is_dir($coverageDir)) {
                mkdir($coverageDir, 0755, true);
            }

            $process = new Process($coverageCommands[$suite], $projectRoot);
            $process->setTimeout(600); // 10 minutes timeout for coverage
            $process->run();

            $exitCode = $process->getExitCode();
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            $coverageUrl = null;
            if ($exitCode === 0) {
                $coverageUrl = '/coverage/product/index.html';
            }

            return new JsonResponse([
                'success' => $exitCode === 0,
                'exitCode' => $exitCode,
                'output' => $output,
                'error' => $errorOutput,
                'coverage_url' => $coverageUrl,
                'suite' => $suite,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (ProcessFailedException $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to generate coverage: ' . $exception->getMessage(),
                'suite' => $suite,
                'timestamp' => date('Y-m-d H:i:s')
            ], 500);
        }
    }

    private function getPhpExecutable(): string
    {
        // Try to detect PHP executable path
        $phpExecutable = 'php'; // Default fallback
        
        // Check if PHP_BINARY is available (most reliable)
        if (defined('PHP_BINARY') && PHP_BINARY) {
            return PHP_BINARY;
        }
        
        // For Windows, try common paths
        if (PHP_OS_FAMILY === 'Windows') {
            $windowsPaths = [
                'D:\\php82\\php\\php.exe',
                'C:\\php\\php.exe',
                'C:\\xampp\\php\\php.exe',
                'C:\\wamp\\bin\\php\\php8.2.12\\php.exe',
                'C:\\laragon\\bin\\php\\php82\\php.exe'
            ];
            
            foreach ($windowsPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
            
            // Try to use 'where php' command
            $whereOutput = shell_exec('where php 2>nul');
            if ($whereOutput) {
                $lines = explode("\n", trim($whereOutput));
                $firstLine = trim($lines[0]);
                if ($firstLine && file_exists($firstLine)) {
                    return $firstLine;
                }
            }
        }
        
        return $phpExecutable;
    }

    private function countTestFiles(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $files = glob($directory . '/*Test.php');
        return count($files);
    }
}
