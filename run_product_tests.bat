@echo off
REM Product Test Runner Script for Windows
REM This script runs all product-related unit tests

echo ==========================================
echo Running Product Management Unit Tests
echo ==========================================

echo Running Product Entity Tests...
php bin/phpunit tests/Entity/ProductTest.php
set ENTITY_EXIT_CODE=%ERRORLEVEL%

echo.
echo Running Product Repository Tests...
php bin/phpunit tests/Repository/ProductRepositoryTest.php
set REPO_EXIT_CODE=%ERRORLEVEL%

echo.
echo Running Product Controller Tests...
php bin/phpunit tests/Controller/ProductControllerTest.php
set CONTROLLER_EXIT_CODE=%ERRORLEVEL%

echo.
echo ==========================================
echo Test Results Summary:
echo ==========================================

if %ENTITY_EXIT_CODE%==0 (
    echo Product Entity Tests: PASSED
) else (
    echo Product Entity Tests: FAILED
)

if %REPO_EXIT_CODE%==0 (
    echo Product Repository Tests: PASSED
) else (
    echo Product Repository Tests: FAILED
)

if %CONTROLLER_EXIT_CODE%==0 (
    echo Product Controller Tests: PASSED
) else (
    echo Product Controller Tests: FAILED
)

set /a OVERALL_EXIT_CODE=%ENTITY_EXIT_CODE%+%REPO_EXIT_CODE%+%CONTROLLER_EXIT_CODE%

if %OVERALL_EXIT_CODE%==0 (
    echo.
    echo All Product Tests PASSED!
) else (
    echo.
    echo Some Product Tests FAILED!
)

exit /b %OVERALL_EXIT_CODE%
