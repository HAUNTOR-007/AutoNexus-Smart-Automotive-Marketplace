<?php
/**
 * Auto Nexus - Vehicles Inventory API
 * Full CRUD operations synchronized with MySQL
 */

require_once __DIR__ . '/db.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetVehicles($pdo);
        break;

    case 'POST':
        handleAddVehicle($pdo);
        break;

    case 'PUT':
        handleUpdateVehicle($pdo);
        break;

    case 'DELETE':
        handleDeleteVehicle($pdo);
        break;

    default:
        sendResponse(false, 'Method not allowed');
        break;
}

function handleGetVehicles($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM `vehicles` ORDER BY `created_at` DESC");
        $rows = $stmt->fetchAll();

        // Format and provide both camelCase and snake_case aliases
        $formatted = array_map(function($car) {
            $badgeClass = !empty($car['badge_class']) ? $car['badge_class'] : 'b-new';
            return [
                'id'          => $car['id'],
                'title'       => $car['title'],
                'brand'       => $car['brand'],
                'year'        => (int)$car['year'],
                'price'       => $car['price'],
                'badge'       => $car['badge'] ?: 'FEATURED',
                'badgeClass'  => $badgeClass,
                'badge_class' => $badgeClass,
                'specs'       => $car['specs'] ?: '',
                'image'       => $car['image'] ?: '',
                'status'      => $car['status'] ?: 'Available',
                'created_at'  => $car['created_at'],
                'updated_at'  => $car['updated_at']
            ];
        }, $rows);

        sendResponse(true, $formatted);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to fetch inventory: ' . $e->getMessage());
    }
}

function handleAddVehicle($pdo) {
    try {
        $data = getRequestData();

        $title = trim($data['title'] ?? '');
        $brand = trim($data['brand'] ?? '');
        $year  = (int)($data['year'] ?? date('Y'));
        $price = trim($data['price'] ?? '');

        if (empty($title) || empty($brand)) {
            sendResponse(false, 'Vehicle title and brand are required.');
        }

        $id = !empty($data['id']) ? trim($data['id']) : ('AN-' . mt_rand(100, 999));
        $badge = trim($data['badge'] ?? 'FEATURED');
        $badgeClass = trim($data['badgeClass'] ?? ($data['badge_class'] ?? 'b-new'));
        $specs = trim($data['specs'] ?? '');
        $image = $data['image'] ?? '';
        $status = trim($data['status'] ?? 'Available');

        // Check if vehicle with same ID already exists
        $checkStmt = $pdo->prepare("SELECT id FROM `vehicles` WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
        if ($checkStmt->fetch()) {
            $id = 'AN-' . mt_rand(100, 999);
        }

        $sql = "INSERT INTO `vehicles` (`id`, `title`, `brand`, `year`, `price`, `badge`, `badge_class`, `specs`, `image`, `status`)
                VALUES (:id, :title, :brand, :year, :price, :badge, :badge_class, :specs, :image, :status)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id'          => $id,
            ':title'       => $title,
            ':brand'       => $brand,
            ':year'        => $year,
            ':price'       => $price,
            ':badge'       => $badge,
            ':badge_class' => $badgeClass,
            ':specs'       => $specs,
            ':image'       => $image,
            ':status'      => $status
        ]);

        $newVehicle = [
            'id'          => $id,
            'title'       => $title,
            'brand'       => $brand,
            'year'        => $year,
            'price'       => $price,
            'badge'       => $badge,
            'badgeClass'  => $badgeClass,
            'badge_class' => $badgeClass,
            'specs'       => $specs,
            'image'       => $image,
            'status'      => $status
        ];

        sendResponse(true, $newVehicle, ['message' => 'Vehicle successfully saved to MySQL database']);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to save vehicle: ' . $e->getMessage());
    }
}

function handleUpdateVehicle($pdo) {
    try {
        $data = getRequestData();
        $id = trim($data['id'] ?? ($_GET['id'] ?? ''));

        if (empty($id)) {
            sendResponse(false, 'Vehicle ID is required for update.');
        }

        $title = trim($data['title'] ?? '');
        $brand = trim($data['brand'] ?? '');
        $year  = (int)($data['year'] ?? 0);
        $price = trim($data['price'] ?? '');
        $badge = trim($data['badge'] ?? '');
        $badgeClass = trim($data['badgeClass'] ?? ($data['badge_class'] ?? ''));
        $specs = trim($data['specs'] ?? '');
        $image = $data['image'] ?? null;
        $status = trim($data['status'] ?? '');

        $fields = [];
        $params = [':id' => $id];

        if ($title !== '') { $fields[] = "`title` = :title"; $params[':title'] = $title; }
        if ($brand !== '') { $fields[] = "`brand` = :brand"; $params[':brand'] = $brand; }
        if ($year > 0)    { $fields[] = "`year` = :year";   $params[':year'] = $year; }
        if ($price !== '') { $fields[] = "`price` = :price"; $params[':price'] = $price; }
        if ($badge !== '') { $fields[] = "`badge` = :badge"; $params[':badge'] = $badge; }
        if ($badgeClass !== '') { $fields[] = "`badge_class` = :badge_class"; $params[':badge_class'] = $badgeClass; }
        if ($specs !== '') { $fields[] = "`specs` = :specs"; $params[':specs'] = $specs; }
        if ($image !== null) { $fields[] = "`image` = :image"; $params[':image'] = $image; }
        if ($status !== '') { $fields[] = "`status` = :status"; $params[':status'] = $status; }

        if (empty($fields)) {
            sendResponse(false, 'No fields to update.');
        }

        $sql = "UPDATE `vehicles` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        sendResponse(true, 'Vehicle updated successfully');
    } catch (Exception $e) {
        sendResponse(false, 'Failed to update vehicle: ' . $e->getMessage());
    }
}

function handleDeleteVehicle($pdo) {
    try {
        $id = trim($_GET['id'] ?? '');
        if (empty($id)) {
            $data = getRequestData();
            $id = trim($data['id'] ?? '');
        }

        if (empty($id)) {
            sendResponse(false, 'Vehicle ID is required for deletion.');
        }

        $stmt = $pdo->prepare("DELETE FROM `vehicles` WHERE `id` = :id");
        $stmt->execute([':id' => $id]);

        sendResponse(true, 'Vehicle entry deleted successfully from MySQL showroom database.');
    } catch (Exception $e) {
        sendResponse(false, 'Failed to delete vehicle: ' . $e->getMessage());
    }
}
