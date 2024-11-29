<?php
// 引入資料庫連線設定
require_once __DIR__ . '/../../../camping_db.php';

header('Content-Type: application/json');

// 檢查請求方法是否為 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '不正確的請求方法']);
    exit;
}

try {
    // 建立 PDO 資料庫連線
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=$db_charset", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 檢查是否有提交分類資料
    if (empty($_POST['categories']) || !is_array($_POST['categories'])) {
        echo json_encode(['success' => false, 'message' => '沒有接收到有效的分類資料']);
        exit;
    }

    $categories = $_POST['categories'];
    $errors = [];
    $successCount = 0;

    // 使用交易處理多筆更新
    $conn->beginTransaction();

    foreach ($categories as $id => $category) {
        // 驗證資料
        $name = trim($category['name']);
        $sortOrder = (int) $category['sort_order'];
        $status = (int) $category['status'];

        if (empty($name)) {
            $errors[] = "分類編號 {$id} 的名稱不可為空";
            continue;
        }

        if (strlen($name) > 50) {
            $errors[] = "分類編號 {$id} 的名稱過長，最多允許 50 個字元。";
            continue;
        }

        if ($sortOrder < 0) {
            $errors[] = "分類編號 {$id} 的排列順序必須為正整數";
            continue;
        }

        if (!in_array($status, [0, 1])) {
            $errors[] = "分類編號 {$id} 的狀態值不正確，應為 0 或 1。";
            continue;
        }

        // 更新分類名稱與排序
        $sql = "UPDATE article_categories 
                SET name = :name, sort_order = :sort_order, status = :status 
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':sort_order', $sortOrder, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = "分類編號 {$id} 更新失敗";
        }
    }

    foreach ($categories as $id => $category) {
        if (isset($category['delete']) && $category['delete'] == 1) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE article_category = ?");
            $stmt->execute([$id]);
            $article_count = $stmt->fetchColumn();

            if ($article_count == 0) {
                $stmt = $conn->prepare("DELETE FROM article_categories WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $successCount++;
                } else {
                    $errors[] = "分類編號 {$id} 刪除失敗";
                }
            } else {
                $errors[] = "分類編號 {$id} 無法刪除，原因：該分類下仍有 {$article_count} 篇文章。";
            }
        }
    }

    // 確認交易或回滾
    if (empty($errors)) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "{$successCount} 筆分類已成功更新"]);
    } else {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => '部分或全部更新失敗', 'details' => ['success_count' => $successCount, 'errors' => $errors]]);
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => '資料庫錯誤', 'error' => $e->getMessage()]);
}
?>
