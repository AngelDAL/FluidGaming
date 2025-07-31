<?php

/**
 * Test database connection
 */

require_once 'config/config.php';
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($db) {
        echo "✅ Database connection successful!\n";

        // Test if tables exist
        $tables = ['users', 'events', 'tournaments', 'point_transactions', 'stands', 'products', 'claims'];

        foreach ($tables as $table) {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);

            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' exists\n";
            } else {
                echo "❌ Table '$table' missing\n";
            }
        }

        // Test admin user
        $stmt = $db->prepare("SELECT nickname, email, role FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();

        if ($admin) {
            echo "✅ Admin user found: " . $admin['nickname'] . " (" . $admin['email'] . ")\n";
        } else {
            echo "❌ Admin user not found\n";
        }
    } else {
        echo "❌ Database connection failed!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
