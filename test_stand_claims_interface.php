<?php
/**
 * Test script for Stand Claims Interface
 */

require_once 'config/database.php';
require_once 'models/Claim.php';
require_once 'models/Stand.php';
require_once 'models/Product.php';
require_once 'models/User.php';

echo "<h2>Testing Stand Claims Interface</h2>\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>1. Testing Database Connection</h3>\n";
    if ($db) {
        echo "✅ Database connection successful\n<br>";
    } else {
        echo "❌ Database connection failed\n<br>";
        exit;
    }
    
    echo "<h3>2. Testing Model Instantiation</h3>\n";
    $claim = new Claim($db);
    $stand = new Stand($db);
    $product = new Product($db);
    $user = new User($db);
    echo "✅ All models instantiated successfully\n<br>";
    
    echo "<h3>3. Testing Stand Manager Functions</h3>\n";
    
    // Test getting stands for a manager
    $query = "SELECT id FROM users WHERE role = 'stand_manager' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $manager = $stmt->fetch();
    
    if ($manager) {
        echo "✅ Found stand manager with ID: " . $manager['id'] . "\n<br>";
        
        // Test getting pending claims for manager
        $pendingClaims = $claim->getPendingClaimsForManager($manager['id']);
        echo "✅ Retrieved " . count($pendingClaims) . " pending claims for manager\n<br>";
        
    } else {
        echo "⚠️ No stand managers found in database\n<br>";
    }
    
    echo "<h3>4. Testing User Search Functionality</h3>\n";
    
    // Test user search
    $query = "SELECT id, nickname, total_points FROM users WHERE role = 'user' LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "✅ Found " . count($users) . " users for search testing:\n<br>";
        foreach ($users as $testUser) {
            echo "- " . $testUser['nickname'] . " (" . $testUser['total_points'] . " pts)\n<br>";
        }
    } else {
        echo "⚠️ No users found for testing\n<br>";
    }
    
    echo "<h3>5. Testing Product Verification</h3>\n";
    
    // Test product verification
    $query = "SELECT p.*, s.name as stand_name FROM products p 
              LEFT JOIN stands s ON p.stand_id = s.id 
              WHERE p.is_active = 1 LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        echo "✅ Found " . count($products) . " active products:\n<br>";
        foreach ($products as $testProduct) {
            echo "- " . $testProduct['name'] . " (" . $testProduct['points_required'] . " pts) - Stand: " . $testProduct['stand_name'] . "\n<br>";
        }
    } else {
        echo "⚠️ No active products found\n<br>";
    }
    
    echo "<h3>6. Testing Points Verification Logic</h3>\n";
    
    if (count($users) > 0 && count($products) > 0) {
        $testUser = $users[0];
        $testProduct = $products[0];
        
        $pointsCheck = $claim->verifyUserPoints($testUser['id'], $testProduct['id']);
        
        if ($pointsCheck) {
            echo "✅ Points verification working:\n<br>";
            echo "- User: " . $testUser['nickname'] . " has " . $pointsCheck['user_points'] . " points\n<br>";
            echo "- Product: " . $pointsCheck['product_name'] . " requires " . $pointsCheck['required_points'] . " points\n<br>";
            echo "- Can claim: " . ($pointsCheck['has_sufficient_points'] ? 'Yes' : 'No') . "\n<br>";
        } else {
            echo "❌ Points verification failed\n<br>";
        }
    }
    
    echo "<h3>7. Testing Claim Uniqueness Check</h3>\n";
    
    if (count($users) > 0 && count($products) > 0) {
        $testUser = $users[0];
        $testProduct = $products[0];
        
        $alreadyClaimed = $claim->hasUserClaimedProduct($testUser['id'], $testProduct['id']);
        echo "✅ Uniqueness check working: User " . ($alreadyClaimed ? 'has already' : 'has not') . " claimed this product\n<br>";
    }
    
    echo "<h3>8. Interface File Check</h3>\n";
    
    if (file_exists('views/stand_claims.php')) {
        echo "✅ Stand claims interface file exists\n<br>";
        
        // Check file size
        $fileSize = filesize('views/stand_claims.php');
        echo "✅ Interface file size: " . number_format($fileSize) . " bytes\n<br>";
        
        // Check if file is readable
        if (is_readable('views/stand_claims.php')) {
            echo "✅ Interface file is readable\n<br>";
        } else {
            echo "❌ Interface file is not readable\n<br>";
        }
    } else {
        echo "❌ Stand claims interface file not found\n<br>";
    }
    
    echo "<h3>9. API Endpoint Check</h3>\n";
    
    if (file_exists('api/claims.php')) {
        echo "✅ Claims API endpoint exists\n<br>";
    } else {
        echo "❌ Claims API endpoint not found\n<br>";
    }
    
    if (file_exists('controllers/ClaimController.php')) {
        echo "✅ Claim controller exists\n<br>";
    } else {
        echo "❌ Claim controller not found\n<br>";
    }
    
    echo "<h3>✅ Stand Claims Interface Test Complete</h3>\n";
    echo "<p>The interface should be accessible at: <a href='views/stand_claims.php'>views/stand_claims.php</a></p>\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "\n<br>";
}
?>