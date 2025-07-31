<?php
/**
 * Test Claims System
 * Tests the Claim model and ClaimController functionality
 */

require_once 'config/database.php';
require_once 'models/Claim.php';
require_once 'models/User.php';
require_once 'models/Product.php';
require_once 'models/Stand.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize models
$claim = new Claim($db);
$user = new User($db);
$product = new Product($db);
$stand = new Stand($db);

echo "<h1>Test Claims System</h1>\n";

// Test 1: Validate claim input
echo "<h2>Test 1: Validate Claim Input</h2>\n";

// Test with invalid data
$errors = $claim->validateClaim('', '', '');
echo "<h3>Invalid data validation:</h3>\n";
echo "<pre>" . print_r($errors, true) . "</pre>\n";

// Test with valid data (assuming user ID 1, product ID 1, stand ID 1 exist)
$errors = $claim->validateClaim(1, 1, 1);
echo "<h3>Valid data validation:</h3>\n";
if (empty($errors)) {
    echo "<p style='color: green;'>✓ Validation passed</p>\n";
} else {
    echo "<pre>" . print_r($errors, true) . "</pre>\n";
}

// Test 2: Check uniqueness validation
echo "<h2>Test 2: Check Uniqueness Validation</h2>\n";

$hasClaimedBefore = $claim->hasUserClaimedProduct(1, 1);
echo "<p>User 1 has claimed product 1 before: " . ($hasClaimedBefore ? 'Yes' : 'No') . "</p>\n";

// Test 3: Verify user points
echo "<h2>Test 3: Verify User Points</h2>\n";

$pointsInfo = $claim->verifyUserPoints(1, 1);
if ($pointsInfo) {
    echo "<h3>Points verification for User 1, Product 1:</h3>\n";
    echo "<pre>" . print_r($pointsInfo, true) . "</pre>\n";
} else {
    echo "<p style='color: red;'>✗ Could not verify points (user or product not found)</p>\n";
}

// Test 4: Create a test claim
echo "<h2>Test 4: Create Test Claim</h2>\n";

// First, let's check if we have test data
$testUserQuery = "SELECT id, nickname, total_points FROM users WHERE role = 'user' LIMIT 1";
$testUserStmt = $db->prepare($testUserQuery);
$testUserStmt->execute();
$testUser = $testUserStmt->fetch();

$testProductQuery = "SELECT p.id, p.name, p.points_required, p.stand_id 
                     FROM products p 
                     JOIN stands s ON p.stand_id = s.id 
                     WHERE p.is_active = 1 
                     LIMIT 1";
$testProductStmt = $db->prepare($testProductQuery);
$testProductStmt->execute();
$testProduct = $testProductStmt->fetch();

if ($testUser && $testProduct) {
    echo "<h3>Test data found:</h3>\n";
    echo "<p>User: {$testUser['nickname']} (ID: {$testUser['id']}, Points: {$testUser['total_points']})</p>\n";
    echo "<p>Product: {$testProduct['name']} (ID: {$testProduct['id']}, Required: {$testProduct['points_required']} points)</p>\n";
    
    // Try to create a claim
    $claimResult = $claim->create($testUser['id'], $testProduct['id'], $testProduct['stand_id']);
    
    echo "<h3>Claim creation result:</h3>\n";
    echo "<pre>" . print_r($claimResult, true) . "</pre>\n";
    
    if ($claimResult['success']) {
        $claimId = $claimResult['claim_id'];
        
        // Test 5: Get claim by ID
        echo "<h2>Test 5: Get Claim by ID</h2>\n";
        $claimDetails = $claim->getById($claimId);
        if ($claimDetails) {
            echo "<h3>Claim details:</h3>\n";
            echo "<pre>" . print_r($claimDetails, true) . "</pre>\n";
        }
        
        // Test 6: Process claim
        echo "<h2>Test 6: Process Claim</h2>\n";
        $processResult = $claim->processClaim($claimId, 1); // Assuming admin user ID 1
        echo "<h3>Process result:</h3>\n";
        echo "<pre>" . print_r($processResult, true) . "</pre>\n";
        
        // Test 7: Try to create duplicate claim
        echo "<h2>Test 7: Try to Create Duplicate Claim</h2>\n";
        $duplicateResult = $claim->create($testUser['id'], $testProduct['id'], $testProduct['stand_id']);
        echo "<h3>Duplicate claim result:</h3>\n";
        echo "<pre>" . print_r($duplicateResult, true) . "</pre>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠ No test data available. Please ensure you have:</p>\n";
    echo "<ul>\n";
    echo "<li>At least one user with role 'user'</li>\n";
    echo "<li>At least one active product in a stand</li>\n";
    echo "</ul>\n";
}

// Test 8: Get claims by user
echo "<h2>Test 8: Get Claims by User</h2>\n";
if ($testUser) {
    $userClaims = $claim->getByUserId($testUser['id']);
    echo "<h3>Claims for user {$testUser['nickname']}:</h3>\n";
    echo "<p>Total claims: " . count($userClaims) . "</p>\n";
    if (!empty($userClaims)) {
        echo "<pre>" . print_r($userClaims[0], true) . "</pre>\n"; // Show first claim
    }
}

// Test 9: Get claims by stand
echo "<h2>Test 9: Get Claims by Stand</h2>\n";
if ($testProduct) {
    $standClaims = $claim->getByStandId($testProduct['stand_id']);
    echo "<h3>Claims for stand ID {$testProduct['stand_id']}:</h3>\n";
    echo "<p>Total claims: " . count($standClaims) . "</p>\n";
}

// Test 10: Get claim statistics
echo "<h2>Test 10: Get Claim Statistics</h2>\n";
$stats = $claim->getClaimStats();
echo "<h3>Overall claim statistics:</h3>\n";
echo "<pre>" . print_r($stats, true) . "</pre>\n";

if ($testProduct) {
    $standStats = $claim->getClaimStats($testProduct['stand_id']);
    echo "<h3>Stand {$testProduct['stand_id']} statistics:</h3>\n";
    echo "<pre>" . print_r($standStats, true) . "</pre>\n";
}

// Test 11: Get pending claims for manager
echo "<h2>Test 11: Get Pending Claims for Manager</h2>\n";
$managerQuery = "SELECT id, nickname FROM users WHERE role IN ('stand_manager', 'admin') LIMIT 1";
$managerStmt = $db->prepare($managerQuery);
$managerStmt->execute();
$manager = $managerStmt->fetch();

if ($manager) {
    $pendingClaims = $claim->getPendingClaimsForManager($manager['id']);
    echo "<h3>Pending claims for manager {$manager['nickname']}:</h3>\n";
    echo "<p>Total pending: " . count($pendingClaims) . "</p>\n";
}

echo "<h2>Tests Completed</h2>\n";
echo "<p>Check the results above to verify the claims system is working correctly.</p>\n";

// Display current database state
echo "<h2>Current Database State</h2>\n";

echo "<h3>Claims Table:</h3>\n";
$claimsQuery = "SELECT c.*, u.nickname as user_nickname, p.name as product_name, s.name as stand_name 
                FROM claims c 
                LEFT JOIN users u ON c.user_id = u.id 
                LEFT JOIN products p ON c.product_id = p.id 
                LEFT JOIN stands s ON c.stand_id = s.id 
                ORDER BY c.timestamp DESC 
                LIMIT 5";
$claimsStmt = $db->prepare($claimsQuery);
$claimsStmt->execute();
$recentClaims = $claimsStmt->fetchAll();

if (!empty($recentClaims)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>User</th><th>Product</th><th>Stand</th><th>Status</th><th>Timestamp</th></tr>\n";
    foreach ($recentClaims as $claim) {
        echo "<tr>";
        echo "<td>{$claim['id']}</td>";
        echo "<td>{$claim['user_nickname']}</td>";
        echo "<td>{$claim['product_name']}</td>";
        echo "<td>{$claim['stand_name']}</td>";
        echo "<td>{$claim['status']}</td>";
        echo "<td>{$claim['timestamp']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p>No claims found in database.</p>\n";
}
?>