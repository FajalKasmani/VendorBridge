<?php
require_once 'config/db_connect.php';

try {
    // 1. Ensure default roles exist
    $pdo->exec("INSERT IGNORE INTO roles (role_id, role_name) VALUES 
        (1, 'Admin'), 
        (2, 'Procurement Officer'), 
        (3, 'Manager'), 
        (4, 'Vendor')");

    // 2. Define users
    $users = [
        ['Admin', 'admin@vendorbridge.com', 'admin123', 1],
        ['Officer', 'officer@vendorbridge.com', 'officer123', 2],
        ['Manager', 'manager@vendorbridge.com', 'manager123', 3],
        ['Vendor', 'vendor@vendorbridge.com', 'vendor123', 4],
    ];

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role_id) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password)");

    foreach ($users as $u) {
        $hashedPassword = password_hash($u[2], PASSWORD_DEFAULT);
        $stmt->execute([$u[0], $u[1], $hashedPassword, $u[3]]);
    }

    echo "<h1>Database seeded successfully!</h1>";
    echo "<p>Demo users created with correct password hashes.</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";

} catch (PDOException $e) {
    echo "<h1>Error seeding database</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
