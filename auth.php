<?php
/**
 * Auto Nexus - Authentication & Users API
 * Full User Management synchronized with MySQL
 */

require_once __DIR__ . '/db.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Support method routing
switch ($action) {
    case 'login':
        handleLogin($pdo);
        break;

    case 'register':
        handleRegister($pdo);
        break;

    case 'users':
        if ($method === 'DELETE' || isset($_GET['delete'])) {
            handleDeleteUser($pdo);
        } else {
            handleGetUsers($pdo);
        }
        break;

    case 'delete_user':
        handleDeleteUser($pdo);
        break;

    default:
        if ($method === 'POST') {
            handleLogin($pdo);
        } elseif ($method === 'GET') {
            handleGetUsers($pdo);
        } else {
            sendResponse(false, 'Invalid action specified');
        }
        break;
}

function handleLogin($pdo) {
    try {
        $data = getRequestData();
        $email = strtolower(trim($data['email'] ?? ''));
        $password = trim($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            sendResponse(false, 'Email and password are required.');
        }

        $salt = '_autonexus_salt_2026';
        $computedSha = hash('sha256', $password . $salt);

        // Administrator default handling:
        if ($email === 'admin@autonexus.com' && $password === 'admin123') {
            // Check if admin user already in DB, otherwise auto-seed admin row into MySQL
            $adminCheck = $pdo->prepare("SELECT * FROM `users` WHERE LOWER(email) = 'admin@autonexus.com' LIMIT 1");
            $adminCheck->execute();
            $adminUser = $adminCheck->fetch();

            if (!$adminUser) {
                $adminId = 'USR-ADM1';
                $insertAdmin = $pdo->prepare("INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`) VALUES (:id, :name, :email, :phone, :hash, 'admin')");
                $insertAdmin->execute([
                    ':id'    => $adminId,
                    ':name'  => 'System Administrator',
                    ':email' => 'admin@autonexus.com',
                    ':phone' => '+91 98765 43210',
                    ':hash'  => $computedSha
                ]);
                $adminUser = [
                    'id'    => $adminId,
                    'name'  => 'System Administrator',
                    'email' => 'admin@autonexus.com',
                    'role'  => 'admin',
                    'phone' => '+91 98765 43210'
                ];
            }

            sendResponse(true, [
                'id'        => $adminUser['id'],
                'name'      => $adminUser['name'],
                'email'     => $adminUser['email'],
                'role'      => 'admin',
                'phone'     => $adminUser['phone'] ?? '',
                'token'     => 'ANX-ADMIN-' . bin2hex(random_bytes(8))
            ], ['user' => [
                'id'    => $adminUser['id'],
                'name'  => $adminUser['name'],
                'email' => $adminUser['email'],
                'role'  => 'admin'
            ]]);
        }

        // Query database for user
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE LOWER(email) = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            sendResponse(false, 'Invalid email address or password.');
        }

        // Verify password
        $storedHash = $user['password_hash'];
        $isValid = false;

        if ($storedHash === $computedSha) {
            $isValid = true;
        } elseif (password_verify($password, $storedHash)) {
            $isValid = true;
        } elseif ($storedHash === $password) {
            $isValid = true;
        }

        if (!$isValid) {
            sendResponse(false, 'Invalid email address or password.');
        }

        $sessionUser = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'] ?: 'user',
            'phone' => $user['phone'] ?: ''
        ];

        sendResponse(true, $sessionUser, [
            'user' => $sessionUser,
            'token' => 'ANX-' . bin2hex(random_bytes(16)),
            'message' => 'Logged in successfully'
        ]);
    } catch (Exception $e) {
        sendResponse(false, 'Login error: ' . $e->getMessage());
    }
}

function handleRegister($pdo) {
    try {
        $data = getRequestData();
        $name     = trim($data['name'] ?? '');
        $email    = strtolower(trim($data['email'] ?? ''));
        $password = trim($data['password'] ?? '');
        $phone    = trim($data['phone'] ?? '');
        $role     = trim($data['role'] ?? 'user');

        if (empty($name) || empty($email) || empty($password)) {
            sendResponse(false, 'Name, email, and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, 'Please provide a valid email address.');
        }

        // Check if email already registered
        $checkStmt = $pdo->prepare("SELECT id FROM `users` WHERE LOWER(email) = :email LIMIT 1");
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            sendResponse(false, 'An account with this email already exists.');
        }

        $userId = 'USR-' . mt_rand(100000, 999999);
        $salt = '_autonexus_salt_2026';
        $passwordHash = hash('sha256', $password . $salt);

        $sql = "INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`)
                VALUES (:id, :name, :email, :phone, :password_hash, :role)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id'            => $userId,
            ':name'          => $name,
            ':email'         => $email,
            ':phone'         => $phone ?: null,
            ':password_hash' => $passwordHash,
            ':role'          => $role
        ]);

        $newUser = [
            'id'        => $userId,
            'name'      => $name,
            'email'     => $email,
            'phone'     => $phone,
            'role'      => $role,
            'createdAt' => date('Y-m-d H:i:s')
        ];

        sendResponse(true, $newUser, [
            'user' => $newUser,
            'token' => 'ANX-' . bin2hex(random_bytes(16)),
            'message' => 'Account created and saved to MySQL database successfully'
        ]);
    } catch (Exception $e) {
        sendResponse(false, 'Registration error: ' . $e->getMessage());
    }
}

function handleGetUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, name, email, phone, role, created_at FROM `users` ORDER BY `created_at` DESC");
        $rows = $stmt->fetchAll();

        $formatted = array_map(function($u) {
            return [
                'id'         => $u['id'],
                'name'       => $u['name'],
                'email'      => $u['email'],
                'phone'      => $u['phone'] ?: '',
                'role'       => $u['role'] ?: 'user',
                'createdAt'  => $u['created_at'],
                'created_at' => $u['created_at']
            ];
        }, $rows);

        sendResponse(true, $formatted);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to fetch users: ' . $e->getMessage());
    }
}

function handleDeleteUser($pdo) {
    try {
        $id = trim($_GET['id'] ?? '');
        if (empty($id)) {
            $data = getRequestData();
            $id = trim($data['id'] ?? '');
        }

        if (empty($id)) {
            sendResponse(false, 'User ID is required for deletion.');
        }

        $stmt = $pdo->prepare("DELETE FROM `users` WHERE `id` = :id");
        $stmt->execute([':id' => $id]);

        sendResponse(true, 'User account deleted successfully from MySQL database');
    } catch (Exception $e) {
        sendResponse(false, 'Failed to delete user: ' . $e->getMessage());
    }
}
