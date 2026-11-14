<?php
require_once __DIR__ . '/db.php';

try {
    $pdo = getDBConnection();
    
    // Check tables in current DB
    $tablesStmt = $pdo->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    $counts = [];
    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
        $counts[$table] = (int)$countStmt->fetchColumn();
    }

    sendResponse(true, [
        'status' => 'online',
        'database' => DB_NAME,
        'tables' => $tables,
        'counts' => $counts,
        'server_time' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}
