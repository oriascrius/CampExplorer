<?php
require_once __DIR__ . '/../../../../camping_db.php';
header('Content-Type: application/json');

try {
    getOwner();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getOwner()
{
    global $db;
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
    
    if (!$id) {
        // 獲取所有營主
        $sql = "SELECT id, email, name, company_name, phone, address, status, created_at 
               FROM owners";
        if (isset($status)) {
            $sql .= " WHERE status = ?";
            $params = [$status];
        } else {
            $params = [];
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $owners]);
        return;
    }

    // 獲取單個營主
    $sql = "SELECT * FROM owners WHERE id = ?";
    if (isset($status)) {
        $sql .= " AND status = ?";
        $params = [$id, $status];
    } else {
        $params = [$id];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$owner) {
        throw new Exception('找不到該營主');
    }

    echo json_encode(['success' => true, 'data' => $owner]);
}