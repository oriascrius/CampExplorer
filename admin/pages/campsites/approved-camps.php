<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    global $db;
    $sql = "
        SELECT 
            ca.application_id,
            ca.owner_name,
            ca.name AS camp_name,
            ca.address,
            ca.created_at,
            o.company_name,
            o.phone,
            COUNT(DISTINCT csa.spot_id) as spot_count,
            COUNT(DISTINCT ci.image_id) as image_count
        FROM camp_applications ca
        LEFT JOIN owners o ON ca.owner_id = o.id
        LEFT JOIN camp_spot_applications csa ON ca.application_id = csa.application_id
        LEFT JOIN camp_images ci ON ca.application_id = ci.application_id
        WHERE ca.status = 1
        GROUP BY ca.application_id, ca.owner_name, ca.name, ca.address, ca.created_at, o.company_name, o.phone
        ORDER BY ca.created_at DESC
    ";

    $stmt = $db->query($sql);
    $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error_message'] = '資料載入失敗，請稍後再試';
    $camps = [];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">已通過審核營地列表</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-nowrap">營地編號</th>
                        <th scope="col" style="min-width: 200px;">營地資訊</th>
                        <th scope="col" style="min-width: 180px;">營主資訊</th>
                        <th scope="col" class="text-center">營地內容</th>
                        <th scope="col" class="text-nowrap">通過時間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($camps)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                目前沒有已通過審核的營地
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($camps as $camp): ?>
                            <tr>
                                <td class="text-nowrap">
                                    <span class="badge bg-success">
                                        #<?= htmlspecialchars($camp['application_id']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold mb-1"><?= htmlspecialchars($camp['camp_name']) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i>
                                        <?= htmlspecialchars($camp['address']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="mb-1"><?= htmlspecialchars($camp['owner_name']) ?></div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-building"></i>
                                        <?= htmlspecialchars($camp['company_name']) ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-telephone"></i>
                                        <?= htmlspecialchars($camp['phone']) ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary me-2">
                                        <i class="bi bi-house-door"></i>
                                        <?= $camp['spot_count'] ?> 個營位
                                    </span>
                                    <span class="badge bg-info">
                                        <i class="bi bi-images"></i>
                                        <?= $camp['image_count'] ?> 張圖片
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <small>
                                        <i class="bi bi-clock"></i>
                                        <?= date('Y/m/d H:i', strtotime($camp['created_at'])) ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>