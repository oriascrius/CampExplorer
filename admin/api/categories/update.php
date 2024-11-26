<?php
require_once __DIR__ . '/../../../camping_db.php';
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'category':
            updateCategory();
            break;
        case 'subcategory':
            updateSubcategory();
            break;
        default:
            throw new Exception('無效的操作');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateCategory()
{
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        throw new Exception('缺少必要參數');
    }

    if (!filter_var($data['id'], FILTER_VALIDATE_INT)) {
        throw new Exception('無效的分類ID');
    }

    $db->beginTransaction();
    try {
        // 檢查分類是否存在
        $check_exist = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $check_exist->execute([$data['id']]);
        $category = $check_exist->fetch(PDO::FETCH_ASSOC);
        if (!$category) {
            throw new Exception('找不到該分類');
        }

        // 如果是更新名稱
        if (isset($data['name'])) {
            if (mb_strlen($data['name']) > 50) {
                throw new Exception('分類名稱不能超過50個字符');
            }
            // 檢查名稱是否重複
            $check_stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $check_stmt->execute([trim($data['name']), $data['id']]);
            if ($check_stmt->fetch()) {
                throw new Exception('分類名稱已存在');
            }
            $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $result = $stmt->execute([trim($data['name']), $data['id']]);
        }
        // 如果是更新狀態
        else if (isset($data['status'])) {
            $stmt = $db->prepare("UPDATE categories SET status = ? WHERE id = ?");
            $result = $stmt->execute([intval($data['status']), $data['id']]);
        } else {
            throw new Exception('缺少更新參數');
        }

        if (!$result) {
            throw new Exception('更新失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'data' => [
                'id' => $data['id'],
                'name' => $data['name'] ?? $category['name'],
                'status' => isset($data['status']) ? intval($data['status']) : $category['status']
            ]
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Update category error: ' . $e->getMessage());
        throw $e;
    }
}

function updateSubcategory()
{
    global $db;
    $data = json_decode(file_get_contents('php://input'), true);

    if (!filter_var($data['id'], FILTER_VALIDATE_INT)) {
        throw new Exception('無效的子分類ID');
    }

    $db->beginTransaction();
    try {
        // 檢查子分類是否存在
        $check_stmt = $db->prepare("SELECT * FROM subcategories WHERE id = ?");
        $check_stmt->execute([$data['id']]);
        $subcategory = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$subcategory) {
            throw new Exception('找不到該子分類');
        }

        // 如果只是更新狀態
        if (isset($data['status']) && !isset($data['name'])) {
            $stmt = $db->prepare("UPDATE subcategories SET status = ? WHERE id = ?");
            $result = $stmt->execute([
                intval($data['status']),
                $data['id']
            ]);
        } else {
            // 名稱更新
            if (empty($data['name'])) {
                throw new Exception('分類名稱不能為空');
            }

            if (mb_strlen($data['name']) > 50) {
                throw new Exception('分類名稱不能超過50個字符');
            }

            // 檢查名稱是否重複
            $name_check = $db->prepare("SELECT id FROM subcategories WHERE name = ? AND id != ? AND category_id = ?");
            $name_check->execute([trim($data['name']), $data['id'], $subcategory['category_id']]);
            if ($name_check->fetch()) {
                throw new Exception('該分類下已存在相同名稱的子分類');
            }

            $stmt = $db->prepare("UPDATE subcategories SET name = ? WHERE id = ?");
            $result = $stmt->execute([
                trim($data['name']),
                $data['id']
            ]);
        }

        if (!$result) {
            throw new Exception('更新失敗');
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'data' => [
                'id' => $data['id'],
                'name' => isset($data['name']) ? trim($data['name']) : $subcategory['name'],
                'status' => isset($data['status']) ? intval($data['status']) : $subcategory['status']
            ]
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log('Update subcategory error: ' . $e->getMessage());
        throw $e;
    }
}
