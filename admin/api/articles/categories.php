<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT id, name FROM article_categories WHERE status = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>