<?php
require_once '../../../camping_db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    
    if (empty($email) || empty($password) || empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '所有欄位都必須填寫']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '此信箱已被註冊']);
            exit;
        }
        
        $sql = "INSERT INTO users (email, password, name, status, created_at) VALUES (?, ?, ?, 1, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $name
        ]);
        
        echo json_encode(['success' => true, 'message' => '註冊成功']);
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '註冊失敗，請稍後再試']);
    }
}