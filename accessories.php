<?php
/**
 * Auto Nexus - Accessories Catalogue API
 * Full CRUD operations synchronized with MySQL
 */

require_once __DIR__ . '/db.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetAccessories($pdo);
        break;

    case 'POST':
        handleAddAccessory($pdo);
        break;

    case 'PUT':
        handleUpdateAccessory($pdo);
        break;

    case 'DELETE':
        handleDeleteAccessory($pdo);
        break;

    default:
        sendResponse(false, 'Method not allowed');
        break;
}

function handleGetAccessories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM `accessories` ORDER BY `created_at` DESC");
        $rows = $stmt->fetchAll();

        $formatted = array_map(function($acc) {
            return [
                'id'         => $acc['id'],
                'title'      => $acc['title'],
                'category'   => $acc['category'],
                'price'      => $acc['price'],
                'badge'      => $acc['badge'] ?: 'UPGRADE',
                'specs'      => $acc['specs'] ?: '',
                'image'      => $acc['image'] ?: '',
                'status'     => $acc['status'] ?: 'In Stock',
                'created_at' => $acc['created_at'],
                'updated_at' => $acc['updated_at']
            ];
        }, $rows);

        sendResponse(true, $formatted);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to fetch accessories: ' . $e->getMessage());
    }
}

function handleAddAccessory($pdo) {
    try {
        $data = getRequestData();

        $title    = trim($data['title'] ?? '');
        $category = trim($data['category'] ?? '');
        $price    = trim($data['price'] ?? '');

        if (empty($title) || empty($category)) {
            sendResponse(false, 'Accessory title and category are required.');
        }

        $id = !empty($data['id']) ? trim($data['id']) : ('ACC-' . mt_rand(100, 999));
        $badge = trim($data['badge'] ?? 'UPGRADE');
        $specs = trim($data['specs'] ?? '');
        $image = $data['image'] ?? '';
        $status = trim($data['status'] ?? 'In Stock');

        // Check if ID already exists
        $checkStmt = $pdo->prepare("SELECT id FROM `accessories` WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
        if ($checkStmt->fetch()) {
            $id = 'ACC-' . mt_rand(100, 999);
        }

        $sql = "INSERT INTO `accessories` (`id`, `title`, `category`, `price`, `badge`, `specs`, `image`, `status`)
                VALUES (:id, :title, :category, :price, :badge, :specs, :image, :status)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id'       => $id,
            ':title'    => $title,
            ':category' => $category,
            ':price'    => $price,
            ':badge'    => $badge,
            ':specs'    => $specs,
            ':image'    => $image,
            ':status'   => $status
        ]);

        $newAcc = [
            'id'       => $id,
            'title'    => $title,
            'category' => $category,
            'price'    => $price,
            'badge'    => $badge,
            'specs'    => $specs,
            'image'    => $image,
            'status'   => $status
        ];

        sendResponse(true, $newAcc, ['message' => 'Accessory successfully saved to MySQL database']);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to save accessory: ' . $e->getMessage());
    }
}

function handleUpdateAccessory($pdo) {
    try {
        $data = getRequestData();
        $id = trim($data['id'] ?? ($_GET['id'] ?? ''));

        if (empty($id)) {
            sendResponse(false, 'Accessory ID is required for update.');
        }

        $title    = trim($data['title'] ?? '');
        $category = trim($data['category'] ?? '');
        $price    = trim($data['price'] ?? '');
        $badge    = trim($data['badge'] ?? '');
        $specs    = trim($data['specs'] ?? '');
        $image    = $data['image'] ?? null;
        $status   = trim($data['status'] ?? '');

        $fields = [];
        $params = [':id' => $id];

        if ($title !== '')    { $fields[] = "`title` = :title";       $params[':title'] = $title; }
        if ($category !== '') { $fields[] = "`category` = :category"; $params[':category'] = $category; }
        if ($price !== '')    { $fields[] = "`price` = :price";       $params[':price'] = $price; }
        if ($badge !== '')    { $fields[] = "`badge` = :badge";       $params[':badge'] = $badge; }
        if ($specs !== '')    { $fields[] = "`specs` = :specs";       $params[':specs'] = $specs; }
        if ($image !== null)  { $fields[] = "`image` = :image";       $params[':image'] = $image; }
        if ($status !== '')   { $fields[] = "`status` = :status";     $params[':status'] = $status; }

        if (empty($fields)) {
            sendResponse(false, 'No fields to update.');
        }

        $sql = "UPDATE `accessories` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        sendResponse(true, 'Accessory updated successfully');
    } catch (Exception $e) {
        sendResponse(false, 'Failed to update accessory: ' . $e->getMessage());
    }
}

function handleDeleteAccessory($pdo) {
    try {
        $id = trim($_GET['id'] ?? '');
        if (empty($id)) {
            $data = getRequestData();
            $id = trim($data['id'] ?? '');
        }

        if (empty($id)) {
            sendResponse(false, 'Accessory ID is required for deletion.');
        }

        $stmt = $pdo->prepare("DELETE FROM `accessories` WHERE `id` = :id");
        $stmt->execute([':id' => $id]);

        sendResponse(true, 'Accessory deleted successfully from MySQL catalogue.');
    } catch (Exception $e) {
        sendResponse(false, 'Failed to delete accessory: ' . $e->getMessage());
    }
}
