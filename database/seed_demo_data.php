<?php
// Script para poblar la base de datos con datos de prueba para FluidGaming
// Ejecuta este archivo una sola vez para llenar las tablas con datos falsos

require_once __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();

function randomName() {
    $names = ['Alex', 'Sam', 'Chris', 'Jordan', 'Taylor', 'Morgan', 'Casey', 'Jamie', 'Robin', 'Drew', 'Sky', 'Riley', 'Cameron', 'Avery', 'Quinn', 'Parker', 'Reese', 'Rowan', 'Sawyer', 'Emerson'];
    $lasts = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Garcia', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Perez', 'Sanchez', 'Ramirez', 'Torres', 'Flores', 'Rivera', 'Gomez'];
    return $names[array_rand($names)] . ' ' . $lasts[array_rand($lasts)];
}

function randomNickname() {
    $adjs = ['Fast', 'Crazy', 'Silent', 'Dark', 'Epic', 'Lucky', 'Wild', 'Brave', 'Furious', 'Magic', 'Shadow', 'Fire', 'Ice', 'Thunder', 'Steel', 'Ghost', 'Ninja', 'Dragon', 'Wolf', 'Lion'];
    $nouns = ['Gamer', 'Player', 'Hunter', 'Wizard', 'Knight', 'Rider', 'Sniper', 'Rogue', 'Beast', 'Hero', 'Legend', 'Master', 'King', 'Queen', 'Samurai', 'Viking', 'Alien', 'Robot', 'Phoenix', 'Falcon'];
    return $adjs[array_rand($adjs)] . $nouns[array_rand($nouns)] . mt_rand(10,99);
}

// 1. Crear 100 usuarios
for ($i = 1; $i <= 100; $i++) {
    $nickname = randomNickname();
    $email = strtolower(str_replace(' ', '', $nickname)) . $i . '@test.com';
    $password_hash = password_hash('test1234', PASSWORD_DEFAULT);
    $points = mt_rand(0, 5000);
    $role = 'user';
    $profile_image = null;
    $stmt = $db->prepare("INSERT INTO users (nickname, email, password_hash, profile_image, role, total_points, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$nickname, $email, $password_hash, $profile_image, $role, $points]);
}

// 2. Crear 5 eventos (events)
$userIds = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
for ($i = 1; $i <= 5; $i++) {
    $name = 'Evento ' . chr(64+$i);
    $desc = 'Evento de prueba número ' . $i;
    $start = date('Y-m-d H:i:s', strtotime("-".(30-($i*5))." days"));
    $end = date('Y-m-d H:i:s', strtotime("+".($i*5)." days"));
    $created_by = $userIds[array_rand($userIds)];
    $stmt = $db->prepare("INSERT INTO events (name, description, start_date, end_date, is_active, created_by, created_at) VALUES (?, ?, ?, ?, 1, ?, NOW())");
    $stmt->execute([$name, $desc, $start, $end, $created_by]);
}

// 3. Crear 10 stands por evento
$eventIds = $db->query("SELECT id FROM events")->fetchAll(PDO::FETCH_COLUMN);
foreach ($eventIds as $eventId) {
    for ($j = 1; $j <= 10; $j++) {
        $name = 'Stand #' . $j;
        $manager_id = $userIds[array_rand($userIds)];
        $stmt = $db->prepare("INSERT INTO stands (name, manager_id, event_id, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $manager_id, $eventId]);
    }
}

// 4. Crear 5 productos por stand
$standIds = $db->query("SELECT id FROM stands")->fetchAll(PDO::FETCH_COLUMN);
foreach ($standIds as $standId) {
    for ($k = 1; $k <= 5; $k++) {
        $name = 'Premio ' . $k;
        $desc = 'Premio especial número ' . $k;
        $points = mt_rand(100, 2000);
        $image_url = null;
        $stmt = $db->prepare("INSERT INTO products (name, description, points_required, stand_id, image_url, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$name, $desc, $points, $standId, $image_url]);
    }
}

// 5. Crear 200 claims aleatorios
$productIds = $db->query("SELECT id, stand_id FROM products")->fetchAll(PDO::FETCH_ASSOC);
for ($i = 1; $i <= 200; $i++) {
    $user_id = $userIds[array_rand($userIds)];
    $product = $productIds[array_rand($productIds)];
    $product_id = $product['id'];
    $stand_id = $product['stand_id'];
    $processed_by = null;
    $status = (mt_rand(0,1) ? 'pending' : 'completed');
    $stmt = $db->prepare("INSERT IGNORE INTO claims (user_id, product_id, stand_id, processed_by, status, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $product_id, $stand_id, $processed_by, $status]);
}

echo "Datos de prueba insertados correctamente.\n";
