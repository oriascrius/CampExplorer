<?php
require_once __DIR__ . '/../../../camping_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    global $db;
    $sql = "SELECT 
                csa.spot_id,
                csa.application_id,
                csa.name AS spot_name,
                csa.capacity,
                csa.price,
                csa.description,
                csa.created_at,
                ca.name AS camp_name,
                ca.owner_name
            FROM camp_spot_applications csa
            JOIN camp_applications ca ON csa.application_id = ca.application_id
            WHERE csa.status = 1
            ORDER BY csa.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $spots = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error_message'] = '資料載入失敗，請稍後再試';
    $spots = [];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">已通過審核營位列表</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-nowrap">營位編號</th>
                        <th scope="col" style="min-width: 200px;">所屬營地</th>
                        <th scope="col" style="min-width: 180px;">營位資訊</th>
                        <th scope="col" class="text-center">價格/容納人數</th>
                        <th scope="col" class="text-nowrap">通過時間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($spots)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                目前沒有已通過審核的營位
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($spots as $spot): ?>
                            <tr>
                                <td class="text-nowrap">
                                    <span class="badge bg-success">
                                        #<?= htmlspecialchars($spot['spot_id']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold mb-1"><?= htmlspecialchars($spot['camp_name']) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i>
                                        <?= htmlspecialchars($spot['owner_name']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-bold mb-1"><?= htmlspecialchars($spot['spot_name']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(mb_substr($spot['description'], 0, 30)) ?>...
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary me-2">
                                        <i class="bi bi-currency-dollar"></i>
                                        NT$ <?= number_format($spot['price']) ?>
                                    </span>
                                    <span class="badge bg-info">
                                        <i class="bi bi-people"></i>
                                        <?= $spot['capacity'] ?> 人
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <small>
                                        <i class="bi bi-clock"></i>
                                        <?= date('Y/m/d H:i', strtotime($spot['created_at'])) ?>
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