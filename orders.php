<?php
/**
 * Auto Nexus - Orders & Inquiries API
 * Full CRUD operations synchronized with MySQL
 */

require_once __DIR__ . '/db.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Handle action parameter on GET or POST
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        handleGetOrders($pdo);
        break;

    case 'POST':
        if ($action === 'update_status') {
            handleUpdateOrderStatus($pdo);
        } else {
            handleAddOrder($pdo);
        }
        break;

    case 'PUT':
        handleUpdateOrderStatus($pdo);
        break;

    case 'DELETE':
        handleDeleteOrder($pdo);
        break;

    default:
        sendResponse(false, 'Method not allowed');
        break;
}

function handleGetOrders($pdo) {
    try {
        $emailFilter = trim($_GET['email'] ?? '');
        $idFilter = trim($_GET['id'] ?? '');

        if (!empty($idFilter)) {
            $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE `id` = :id LIMIT 1");
            $stmt->execute([':id' => $idFilter]);
            $order = $stmt->fetch();
            if (!$order) {
                sendResponse(false, 'Order not found');
            }
            sendResponse(true, formatOrderRow($order));
        }

        if (!empty($emailFilter)) {
            $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE LOWER(`customer_email`) = LOWER(:email) ORDER BY `created_at` DESC");
            $stmt->execute([':email' => $emailFilter]);
        } else {
            $stmt = $pdo->query("SELECT * FROM `orders` ORDER BY `created_at` DESC");
        }

        $rows = $stmt->fetchAll();
        $formatted = array_map('formatOrderRow', $rows);

        sendResponse(true, $formatted);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to fetch orders: ' . $e->getMessage());
    }
}

function formatOrderRow($order) {
    $dateStr = !empty($order['created_at']) ? date('M d, Y', strtotime($order['created_at'])) : date('M d, Y');
    $note = $order['note'] ?: ($order['items_json'] ?: '');
    return [
        'id'             => $order['id'],
        'user_id'        => $order['user_id'],
        'customerName'   => $order['customer_name'],
        'customer_name'  => $order['customer_name'],
        'email'          => $order['customer_email'],
        'customer_email' => $order['customer_email'],
        'phone'          => $order['customer_phone'],
        'customer_phone' => $order['customer_phone'],
        'type'           => $order['type'],
        'vehicle_id'     => $order['vehicle_id'],
        'accessory_id'   => $order['accessory_id'],
        'note'           => $note,
        'items_json'     => $order['items_json'] ?: $note,
        'details'        => $note,
        'total_price'    => $order['total_price'] ?: '',
        'image'          => $order['image'] ?: '',
        'status'         => $order['status'] ?: 'Pending',
        'date'           => $dateStr,
        'created_at'     => $order['created_at'],
        'updated_at'     => $order['updated_at']
    ];
}

function handleAddOrder($pdo) {
    try {
        $data = getRequestData();

        $customerName = trim($data['customer_name'] ?? ($data['customerName'] ?? ''));
        $email        = trim($data['customer_email'] ?? ($data['email'] ?? ''));
        $phone        = trim($data['customer_phone'] ?? ($data['phone'] ?? ''));
        $type         = trim($data['type'] ?? 'Inquiry');
        $note         = trim($data['note'] ?? ($data['details'] ?? ''));
        $itemsJson    = trim($data['items_json'] ?? ($note ?: ''));
        $totalPrice   = trim($data['total_price'] ?? ($data['price'] ?? ''));
        $image        = $data['image'] ?? '';
        $status       = trim($data['status'] ?? 'Pending');
        $userId       = !empty($data['user_id']) ? trim($data['user_id']) : null;
        $vehicleId    = !empty($data['vehicle_id']) ? trim($data['vehicle_id']) : null;
        $accessoryId  = !empty($data['accessory_id']) ? trim($data['accessory_id']) : null;

        if (empty($customerName)) {
            $customerName = 'Valued Customer';
        }

        $id = !empty($data['id']) ? trim($data['id']) : ('ORD-' . mt_rand(1000, 9999));

        // Ensure unique ID
        $checkStmt = $pdo->prepare("SELECT id FROM `orders` WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
        if ($checkStmt->fetch()) {
            $id = 'ORD-' . mt_rand(1000, 9999);
        }

        // Auto-link user_id if email matches a registered user and user_id not explicitly set
        if (!$userId && !empty($email)) {
            $userStmt = $pdo->prepare("SELECT id FROM `users` WHERE LOWER(email) = LOWER(:email) LIMIT 1");
            $userStmt->execute([':email' => $email]);
            $foundUser = $userStmt->fetch();
            if ($foundUser) {
                $userId = $foundUser['id'];
            }
        }

        // Verify foreign key integrity: if vehicle_id does not exist, set to null
        if ($vehicleId) {
            $vStmt = $pdo->prepare("SELECT id FROM `vehicles` WHERE id = :id");
            $vStmt->execute([':id' => $vehicleId]);
            if (!$vStmt->fetch()) $vehicleId = null;
        }

        // Verify foreign key integrity: if accessory_id does not exist, set to null
        if ($accessoryId) {
            $aStmt = $pdo->prepare("SELECT id FROM `accessories` WHERE id = :id");
            $aStmt->execute([':id' => $accessoryId]);
            if (!$aStmt->fetch()) $accessoryId = null;
        }

        $sql = "INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `type`, `vehicle_id`, `accessory_id`, `note`, `items_json`, `total_price`, `image`, `status`)
                VALUES (:id, :user_id, :customer_name, :customer_email, :customer_phone, :type, :vehicle_id, :accessory_id, :note, :items_json, :total_price, :image, :status)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id'             => $id,
            ':user_id'        => $userId,
            ':customer_name'  => $customerName,
            ':customer_email' => $email ?: null,
            ':customer_phone' => $phone ?: null,
            ':type'           => $type,
            ':vehicle_id'     => $vehicleId,
            ':accessory_id'   => $accessoryId,
            ':note'           => $note ?: $itemsJson,
            ':items_json'     => $itemsJson,
            ':total_price'    => $totalPrice ?: null,
            ':image'          => $image ?: null,
            ':status'         => $status
        ]);

        $newOrder = formatOrderRow([
            'id'             => $id,
            'user_id'        => $userId,
            'customer_name'  => $customerName,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'type'           => $type,
            'vehicle_id'     => $vehicleId,
            'accessory_id'   => $accessoryId,
            'note'           => $note ?: $itemsJson,
            'items_json'     => $itemsJson,
            'total_price'    => $totalPrice,
            'image'          => $image,
            'status'         => $status,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        sendResponse(true, $newOrder, ['message' => 'Order / inquiry successfully saved to MySQL database']);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to save order: ' . $e->getMessage());
    }
}

function handleUpdateOrderStatus($pdo) {
    try {
        $data = getRequestData();
        $id = trim($data['id'] ?? ($_GET['id'] ?? ''));
        $status = trim($data['status'] ?? '');

        if (empty($id) || empty($status)) {
            sendResponse(false, 'Order ID and new status are required.');
        }

        $stmt = $pdo->prepare("UPDATE `orders` SET `status` = :status WHERE `id` = :id");
        $stmt->execute([
            ':status' => $status,
            ':id'     => $id
        ]);

        sendResponse(true, 'Order status updated successfully in MySQL database');
    } catch (Exception $e) {
        sendResponse(false, 'Failed to update order status: ' . $e->getMessage());
    }
}

function handleDeleteOrder($pdo) {
    try {
        $id = trim($_GET['id'] ?? '');
        if (empty($id)) {
            $data = getRequestData();
            $id = trim($data['id'] ?? '');
        }

        if (empty($id)) {
            sendResponse(false, 'Order ID is required for deletion.');
        }

        $stmt = $pdo->prepare("DELETE FROM `orders` WHERE `id` = :id");
        $stmt->execute([':id' => $id]);

        sendResponse(true, 'Order record deleted successfully from MySQL database');
    } catch (Exception $e) {
        sendResponse(false, 'Failed to delete order: ' . $e->getMessage());
    }
}
