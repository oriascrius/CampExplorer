<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('未登入');
    }

    require_once __DIR__ . '/../../../camping_db.php';

    global $db;
    $sql = "SELECT 
        csa.spot_id,
        csa.name AS spot_name,
        csa.capacity,
        csa.price,
        csa.status,
        csa.description,
        ca.name AS camp_name,
        ca.status AS application_status,
        ca.operation_status,
        csi.image_path
        FROM camp_spot_applications csa
        JOIN camp_applications ca ON csa.application_id = ca.application_id
        LEFT JOIN camp_spot_images csi ON csa.spot_id = csi.spot_id
        WHERE ca.owner_id = :owner_id";

    $stmt = $db->prepare($sql);
    $stmt->execute([':owner_id' => $_SESSION['owner_id']]);
    $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_spots = array_map(function($spot) {
        return [
            'spot_id' => $spot['spot_id'],
            'spot_name' => $spot['spot_name'],
            'capacity' => $spot['capacity'],
            'price' => $spot['price'],
            'image_path' => !empty($spot['image_path']) ? '/CampExplorer/uploads/spots/' . $spot['image_path'] : '',
            'camp_name' => $spot['camp_name'],
            'application_status' => $spot['application_status'],
            'status' => $spot['status']
        ];
    }, $spots);

    echo json_encode([
        'success' => true,
        'spots' => $formatted_spots
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
