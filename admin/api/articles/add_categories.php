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

    // 使用交易處理多筆新增
    $conn->beginTransaction();

    foreach ($categories as $category) {
        // 驗證資料，跳過空白資料
        $name = isset($category['name']) ? trim($category['name']) : '';
        $sortOrder = isset($category['sort_order']) ? (int) $category['sort_order'] : null;
        $status = isset($category['status']) ? (int) $category['status'] : null;

        // 檢查欄位是否都填寫
        if (empty($name) || $sortOrder === null || $status === null) {
            // 跳過該筆資料
            continue;
        }

        // 驗證資料
        if (strlen($name) > 50) {
            $errors[] = "分類名稱過長，最多允許 50 個字元。";
            continue;
        }

        if ($sortOrder < 0) {
            $errors[] = "排列順序必須為正整數";
            continue;
        }

        if (!in_array($status, [0, 1])) {
            $errors[] = "狀態值不正確，應為 0 或 1。";
            continue;
        }

        // 新增分類資料
        $sql = "INSERT INTO article_categories (name, sort_order, status) VALUES (:name, :sort_order, :status)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':sort_order', $sortOrder, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = "分類 {$name} 新增失敗";
        }
    }

    // 確認交易或回滾
    if (empty($errors)) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "{$successCount} 筆分類已成功新增"]);
    } else {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => '部分或全部新增失敗', 'details' => ['success_count' => $successCount, 'errors' => $errors]]);
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => '資料庫錯誤', 'error' => $e->getMessage()]);
}
?>
