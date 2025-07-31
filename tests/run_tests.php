<?php
/**
 * Test runner script for the tournament points system
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "=== Sistema de Puntos y Torneos - Test Suite ===\n\n";

// We're using our own simple testing framework

// Test database connection (skip if not available)
try {
    $testDb = TestDatabase::getInstance();
    $connection = $testDb->getConnection();
    echo "✓ Test database connection successful\n";
    $databaseAvailable = true;
} catch (Exception $e) {
    echo "⚠️  Test database not available: " . $e->getMessage() . "\n";
    echo "Running tests that don't require database...\n";
    $databaseAvailable = false;
}

// Create test database schema if needed
if ($databaseAvailable) {
    try {
        // Read and execute schema
        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            // Remove database creation commands and use existing test database
            $schema = preg_replace('/CREATE DATABASE.*?;/i', '', $schema);
            $schema = preg_replace('/USE.*?;/i', '', $schema);
            
            $connection->exec($schema);
            echo "✓ Test database schema created/updated\n";
        }
    } catch (Exception $e) {
        echo "Warning: Could not create test database schema: " . $e->getMessage() . "\n";
        $databaseAvailable = false;
    }
}

echo "\n=== Running Unit Tests ===\n";

// Run unit tests
$unitTestFiles = glob(__DIR__ . '/Unit/*Test.php');
$unitTestsPassed = 0;
$unitTestsFailed = 0;

foreach ($unitTestFiles as $testFile) {
    $testClass = basename($testFile, '.php');
    echo "\nRunning $testClass...\n";
    
    try {
        require_once $testFile;
        
        if (class_exists($testClass)) {
            // Skip database-dependent tests if database is not available
            if (!$databaseAvailable && in_array($testClass, ['UserTest', 'PointTransactionTest', 'ClaimTest'])) {
                echo "  Skipped (requires database)\n";
                continue;
            }
            $testInstance = new $testClass();
            $reflection = new ReflectionClass($testClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            
            $classTestsPassed = 0;
            $classTestsFailed = 0;
            
            foreach ($methods as $method) {
                if (strpos($method->getName(), 'test') === 0) {
                    try {
                        $testInstance->setUp();
                        $testInstance->{$method->getName()}();
                        $testInstance->tearDown();
                        
                        echo "  ✓ " . $method->getName() . "\n";
                        $classTestsPassed++;
                        $unitTestsPassed++;
                    } catch (Exception $e) {
                        echo "  ✗ " . $method->getName() . " - " . $e->getMessage() . "\n";
                        $classTestsFailed++;
                        $unitTestsFailed++;
                    }
                }
            }
            
            echo "  Tests passed: $classTestsPassed, Failed: $classTestsFailed\n";
        }
    } catch (Exception $e) {
        echo "  Error loading test class: " . $e->getMessage() . "\n";
        $unitTestsFailed++;
    }
}

echo "\n=== Running Integration Tests ===\n";

// Run integration tests
$integrationTestFiles = glob(__DIR__ . '/Integration/*Test.php');
$integrationTestsPassed = 0;
$integrationTestsFailed = 0;

foreach ($integrationTestFiles as $testFile) {
    $testClass = basename($testFile, '.php');
    echo "\nRunning $testClass...\n";
    
    try {
        require_once $testFile;
        
        if (class_exists($testClass)) {
            // Skip all integration tests if database is not available
            if (!$databaseAvailable) {
                echo "  Skipped (requires database)\n";
                continue;
            }
            $testInstance = new $testClass();
            $reflection = new ReflectionClass($testClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            
            $classTestsPassed = 0;
            $classTestsFailed = 0;
            
            foreach ($methods as $method) {
                if (strpos($method->getName(), 'test') === 0) {
                    try {
                        $testInstance->setUp();
                        $testInstance->{$method->getName()}();
                        $testInstance->tearDown();
                        
                        echo "  ✓ " . $method->getName() . "\n";
                        $classTestsPassed++;
                        $integrationTestsPassed++;
                    } catch (Exception $e) {
                        echo "  ✗ " . $method->getName() . " - " . $e->getMessage() . "\n";
                        $classTestsFailed++;
                        $integrationTestsFailed++;
                    }
                }
            }
            
            echo "  Tests passed: $classTestsPassed, Failed: $classTestsFailed\n";
        }
    } catch (Exception $e) {
        echo "  Error loading test class: " . $e->getMessage() . "\n";
        $integrationTestsFailed++;
    }
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Unit Tests - Passed: $unitTestsPassed, Failed: $unitTestsFailed\n";
echo "Integration Tests - Passed: $integrationTestsPassed, Failed: $integrationTestsFailed\n";

$totalPassed = $unitTestsPassed + $integrationTestsPassed;
$totalFailed = $unitTestsFailed + $integrationTestsFailed;
$totalTests = $totalPassed + $totalFailed;

echo "Total Tests: $totalTests, Passed: $totalPassed, Failed: $totalFailed\n";

if ($totalFailed > 0) {
    echo "\n⚠️  Some tests failed. Please review the output above.\n";
    exit(1);
} else {
    echo "\n🎉 All tests passed!\n";
    exit(0);
}
?>