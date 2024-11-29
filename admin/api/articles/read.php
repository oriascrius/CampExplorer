<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    // 獲取文章 id 參數
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if (!$id) {
        throw new Exception('Missing article ID');
    }

    // 查詢單一文章資料
    $sql = "SELECT 
                articles.article_category, 
                articles.title,
                articles.subtitle,
                articles.content,
                articles.image_name, 
                articles.status,
                DATE(articles.created_at) AS created_date,
                admins.name AS creator_name,
                article_categories.name AS category_name,
                COUNT(article_like.id) AS like_count
            FROM articles
            LEFT JOIN admins ON articles.created_by = admins.id
            LEFT JOIN article_categories ON articles.article_category = article_categories.id
            LEFT JOIN article_like ON articles.id = article_like.article_id
            WHERE articles.id = :id
            GROUP BY articles.id, 
                     articles.article_category, 
                     articles.title,
                     articles.subtitle,
                     articles.content,
                     articles.image_name, 
                     articles.status,
                     DATE(articles.created_at),
                     admins.name,
                     article_categories.name";


    // 使用準備語句來執行查詢
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // 取得查詢結果
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    // 若有找到文章，返回成功資料，否則返回錯誤
    if ($article) {
        echo json_encode([
            'success' => true,
            'data' => $article
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '文章未找到'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
