<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['action'])) {
        throw new Exception('缺少必要參數');
    }

    $action = $_GET['action'];
    $allowed_actions = ['category', 'subcategory'];

    if (!in_array($action, $allowed_actions)) {
        throw new Exception('無效的操作類型');
    }

    switch ($action) {
        case 'category':
            createCategory();
            break;
        case 'subcategory':
            createSubcategory();
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}

// 從原始檔案複製 createCategory() 和 createSubcategory() 函數

function createCategory()
{
    global $db;
    $raw_input = file_get_contents('php://input');
    
    // 添加錯誤日誌
    error_log('Creating category with raw input: ' . $raw_input);
    
    $data = json_decode($raw_input, true);
    if (!$data) {
        throw new Exception('無效的請求數據: ' . json_last_error_msg());
    }
    
    error_log('Decoded category data: ' . print_r($data, true));

    // 基本驗證
    if (empty($data['name'])) {
        throw new Exception('分類名稱為必填');
    }

    if (!is_string($data['name'])) {
        throw new Exception('分類名稱必須為字串');
    }

    if (mb_strlen($data['name']) > 50) {
        throw new Exception('分類名稱不能超過50個字符');
    }

    $db->beginTransaction();
    try {
        // 檢查名稱是否重複
        $check_stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND status = 1");
        $check_stmt->execute([trim($data['name'])]);
        if ($check_stmt->fetch()) {
            throw new Exception('分類名稱已存在');
        }

        // 獲取最大排序值
        $max_sort = $db->query("SELECT COALESCE(MAX(sort_order), -1) FROM categories")->fetchColumn();
        $next_sort = $max_sort + 1;

        // 執行插入
        $stmt = $db->prepare("INSERT INTO categories (name, sort_order, status) VALUES (?, ?, ?)");
        $result = $stmt->execute([
            trim($data['name']),
            $next_sort,
            intval($data['status'] ?? 1)
        ]);

        if (!$result) {
            throw new Exception('新增失敗');
        }

        $category_id = $db->lastInsertId();
        
        $db->commit();
        
        // 返回完整的響應數據
        echo json_encode([
            'success' => true,
            'message' => '新增成功',
            'data' => [
                'id' => $category_id,
                'name' => trim($data['name']),
                'sort_order' => $next_sort,
                'status' => intval($data['status'] ?? 1)
            ]
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Category creation error: ' . $e->getMessage());
        throw $e;
    }
}

// 創建子分類
function createSubcategory()
{
    global $db;
    $raw_input = file_get_contents('php://input');
    error_log('Raw subcategory input: ' . $raw_input);

    $data = json_decode($raw_input, true);
    error_log('Decoded subcategory data: ' . print_r($data, true));

    if (!$data) {
        $json_error = json_last_error_msg();
        throw new Exception('無效的請求數據: ' . $json_error);
    }

    // 基本数据验证
    if (!is_array($data)) {
        throw new Exception('無效的請求數據');
    }

    // 验证必填字段
    if (empty($data['name'])) {
        throw new Exception('分類名稱為必填');
    }

    if (empty($data['category_id'])) {
        throw new Exception('主分類ID為必填');
    }

    // 验证字段格式
    if (!filter_var($data['category_id'], FILTER_VALIDATE_INT)) {
        throw new Exception('無效的主分類ID格式');
    }

    if (!is_string($data['name'])) {
        throw new Exception('分類名稱必須為字串');
    }

    if (mb_strlen($data['name']) > 50) {
        throw new Exception('分類名稱不能超過50個字符');
    }

    // 验证状态值
    if (isset($data['status']) && !in_array(intval($data['status']), [0, 1], true)) {
        throw new Exception('狀態值無效');
    }

    $db->beginTransaction();
    try {
        // 檢查主分類是否存在
        $check_stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $check_stmt->execute([$data['category_id']]);
        if (!$check_stmt->fetch()) {
            throw new Exception('主分類不存在');
        }

        // 檢查名稱是否重複
        $name_check = $db->prepare("SELECT id FROM subcategories WHERE name = ? AND category_id = ?");
        $name_check->execute([trim($data['name']), $data['category_id']]);
        if ($name_check->fetch()) {
            throw new Exception('該分類下已存在相同名稱的子分類');
        }

        // 獲取當前分類下最大的排序值
        $max_sort = $db->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM subcategories WHERE category_id = ?");
        $max_sort->execute([$data['category_id']]);
        $next_sort = $max_sort->fetchColumn() + 1;

        $stmt = $db->prepare("INSERT INTO subcategories (category_id, name, sort_order, status) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([
            $data['category_id'],
            trim($data['name']),
            $next_sort,
            intval($data['status'] ?? 1)
        ]);

        if (!$result) {
            throw new Exception('新增失敗');
        }

        $subcategory_id = $db->lastInsertId();

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '新增成功',
            'data' => [
                'id' => $subcategory_id,
                'category_id' => $data['category_id'],
                'name' => trim($data['name']),
                'sort_order' => $next_sort,
                'status' => intval($data['status'] ?? 1),
                'parent_name' => $parent_category['name'] ?? ''
            ]
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
