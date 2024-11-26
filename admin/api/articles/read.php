<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    // 獲取排序參數
    $sort = $_GET['sort'] ?? 'created_at';
    $order = strtoupper($_GET['order'] ?? 'DESC');
    
    // 驗證排序欄位
    $allowedFields = ['title', 'status', 'views', 'created_at', 'updated_at'];
    if (!in_array($sort, $allowedFields)) {
        $sort = 'created_at';
    }
    
    // 驗證排序方向
    if (!in_array($order, ['ASC', 'DESC'])) {
        $order = 'DESC';
    }
    
    // 查詢文章列表
    $sql = "SELECT id, title, status, views, created_at, updated_at 
            FROM articles 
            ORDER BY {$sort} {$order}";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $articles
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}