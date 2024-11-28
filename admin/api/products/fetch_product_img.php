<?php
require_once __DIR__ . "../../../../camping_db.php";
header('Content-Type: application/json');


$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    echo json_encode(['success' => false, 'message' => '商品ID未提供']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT id, image_path, is_main FROM product_images WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'images' => $images]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '伺服器錯誤: ' . $e->getMessage()]);
}
