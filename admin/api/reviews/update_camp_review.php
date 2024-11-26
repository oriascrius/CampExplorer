<?php
require_once '../../includes/db.php';
require_once '../../includes/session.php';

// 設置響應頭
header('Content-Type: application/json');

try {
    // 檢查登入狀態
    if (!isset($_SESSION['admin_id'])) {
        throw new Exception('請先登入');
    }

    // 檢查必要參數
    if (empty($_POST['application_id']) || !isset($_POST['status']) || empty($_POST['comment'])) {
        throw new Exception('請填寫所有必要欄位');
    }

    $application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment']);

    // 參數驗證
    if (!$application_id) {
        throw new Exception('無效的申請編號');
    }
    if (!in_array($status, [0, 1, 2])) {
        throw new Exception('無效的審核狀態');
    }
    if (strlen($comment) < 10) {
        throw new Exception('審核意見至少需要10個字');
    }

    $conn = connectDB();
    
    // 開始事務
    $conn->beginTransaction();

    try {
        // 檢查申請是否存在
        $checkStmt = $conn->prepare("
            SELECT application_id 
            FROM camp_applications 
            WHERE application_id = ?
        ");
        $checkStmt->execute([$application_id]);
        
        if (!$checkStmt->fetch()) {
            throw new Exception('找不到此申請記錄');
        }

        // 更新申請狀態
        $updateAppStmt = $conn->prepare("
            UPDATE camp_applications 
            SET status = ?, 
                updated_at = NOW() 
            WHERE application_id = ?
        ");
        
        $updateResult = $updateAppStmt->execute([$status, $application_id]);
        
        if (!$updateResult) {
            throw new Exception('更新申請狀態失敗');
        }

        // 檢查是否已有審核記錄
        $checkReviewStmt = $conn->prepare("
            SELECT review_id 
            FROM campsite_reviews 
            WHERE application_id = ?
        ");
        $checkReviewStmt->execute([$application_id]);
        $existingReview = $checkReviewStmt->fetch();

        if ($existingReview) {
            // 更新現有審核記錄
            $reviewStmt = $conn->prepare("
                UPDATE campsite_reviews 
                SET status = ?,
                    comment = ?,
                    admin_id = ?,
                    reviewed_at = NOW()
                WHERE application_id = ?
            ");
            $reviewResult = $reviewStmt->execute([
                $status,
                $comment,
                $_SESSION['admin_id'],
                $application_id
            ]);
        } else {
            // 新增審核記錄
            $reviewStmt = $conn->prepare("
                INSERT INTO campsite_reviews 
                (application_id, admin_id, status, comment, reviewed_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $reviewResult = $reviewStmt->execute([
                $application_id,
                $_SESSION['admin_id'],
                $status,
                $comment
            ]);
        }

        if (!$reviewResult) {
            throw new Exception('更新審核記錄失敗');
        }

        // 提交事務
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => '審核完成',
            'data' => [
                'application_id' => $application_id,
                'status' => $status
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '資料庫錯誤：' . $e->getMessage()
    ]);
}
