<?php
require_once __DIR__ . '/../../../camping_db.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: ../../../owner-login.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];
$camps = [];

// 獲取營地列表
try {
    // 查詢所有營地狀態
    $stmt = $db->prepare("
        SELECT 
            ca.application_id,
            ca.name,
            ca.status,
            ca.created_at,
            ca.updated_at,
            ca.description,
            ca.address,
            ca.rules,
            ca.notice,
            cr.comment as admin_comment,
            CASE ca.status
                WHEN 0 THEN '審核中'
                WHEN 1 THEN '已通過'
                WHEN 2 THEN '已退回'
                ELSE '未知'
            END as status_text
        FROM camp_applications ca
        LEFT JOIN campsite_reviews cr ON ca.application_id = cr.review_id
        WHERE ca.owner_id = ?
        ORDER BY ca.created_at DESC
    ");

    $stmt->execute([$owner_id]);
    $camps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "查詢敗：" . $e->getMessage();
}

// 計算不同狀態的營地數量
$stats = [
    'total' => count($camps),
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

foreach ($camps as $camp) {
    switch ($camp['status']) {
        case 0:
            $stats['pending']++;
            break;
        case 1:
            $stats['approved']++;
            break;
        case 2:
            $stats['rejected']++;
            break;
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="/CampExplorer/owner/includes/style.css" rel="stylesheet">
<link href="/CampExplorer/owner/includes/pages-common.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<div class="page-container">
    <div class="stats-container mb-4">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-card" onclick="filterCamps('all')">
                    <div class="stat-icon">
                        <i class="bi bi-grid-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $stats['total'] ?></h3>
                        <p class="stat-label">總營地數</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="filterCamps('pending')">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $stats['pending'] ?></h3>
                        <p class="stat-label">審核中</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="filterCamps('approved')">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $stats['approved'] ?></h3>
                        <p class="stat-label">已通過</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" onclick="filterCamps('rejected')">
                    <div class="stat-icon">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $stats['rejected'] ?></h3>
                        <p class="stat-label">已退回</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- 主要內容區 -->
    <div class="content-wrapper">
        <div class="page-header">
            <h1 class="page-title">營地狀態</h1>
        </div>
        <div class="card">
            <?php if (empty($camps)): ?>
                <div class="text-center text-muted py-3">
                    <p>目前還沒有營地資料</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="spot-list">
                        <thead>
                            <tr>
                                <th>營地名稱</th>
                                <th>審核狀態</th>
                                <th>申請時間</th>
                                <th>最後更新</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($camps as $camp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($camp['name']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $camp['status'] ?>"><?= $camp['status_text'] ?></span>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($camp['created_at'])) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($camp['updated_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#campDetailModal"
                                            data-camp-name="<?= htmlspecialchars($camp['name']) ?>"
                                            data-camp-address="<?= htmlspecialchars($camp['address']) ?>"
                                            data-camp-status="<?= htmlspecialchars($camp['status_text']) ?>"
                                            data-camp-created="<?= date('Y-m-d H:i', strtotime($camp['created_at'])) ?>"
                                            data-camp-updated="<?= date('Y-m-d H:i', strtotime($camp['updated_at'])) ?>"
                                            data-camp-description="<?= htmlspecialchars($camp['description']) ?>"
                                            data-camp-rules="<?= htmlspecialchars($camp['rules']) ?>"
                                            data-camp-notice="<?= htmlspecialchars($camp['notice']) ?>"
                                            data-camp-comment="<?= htmlspecialchars($camp['admin_comment'] ?? '') ?>">
                                            查看詳情
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal -->
<div class="modal fade" id="campDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">營地詳細資訊</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="camp-info">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><span class="info-label">營地名稱：</span> <span id="modalCampName"></span></p>
                            <p><span class="info-label">營地地址：</span> <span id="modalCampAddress"></span></p>
                            <p><span class="info-label">申請時間：</span> <span id="modalCampCreated"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><span class="info-label">審核狀態：</span> <span id="modalCampStatus"></span></p>
                            <p><span class="info-label">最後更新：</span> <span id="modalCampUpdated"></span></p>
                            <p><span class="info-label">審核意見：</span> <span id="modalCampComment"></span></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6 class="info-label">營地介紹</h6>
                        <div id="modalCampDescription" class="p-3 bg-light rounded"></div>
                    </div>
                    <div class="mb-3">
                        <h6 class="info-label">營地規則</h6>
                        <div id="modalCampRules" class="p-3 bg-light rounded"></div>
                    </div>
                    <div class="mb-3">
                        <h6 class="info-label">注意事項</h6>
                        <div id="modalCampNotice" class="p-3 bg-light rounded"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 表格行點擊事件
        document.querySelectorAll('table tbody tr').forEach(row => {
            row.addEventListener('click', function(e) {
                // 如果點擊的是按鈕本身，不需要觸發行點擊事件
                if (e.target.closest('.btn')) {
                    return;
                }

                // 找到該行中的詳細資訊按鈕
                const detailButton = this.querySelector('.btn-outline-primary');
                if (detailButton) {
                    detailButton.click();
                }
            });
        });

        // 其他現有的 DOMContentLoaded 事件處理程式
        const container = document.querySelector('.container-fluid');
        const sideNav = document.querySelector('.side-nav');

        if (sideNav) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.target.classList.contains('collapsed')) {
                        container.classList.add('nav-collapsed');
                    } else {
                        container.classList.remove('nav-collapsed');
                    }
                });
            });

            observer.observe(sideNav, {
                attributes: true,
                attributeFilter: ['class']
            });
        }

        const campDetailModal = document.getElementById('campDetailModal');
        if (campDetailModal) {
            campDetailModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const modalBody = this.querySelector('.modal-body');

                // 更新 Modal 內容
                document.getElementById('modalCampName').textContent = button.dataset.campName || '無資料';
                document.getElementById('modalCampAddress').textContent = button.dataset.campAddress || '無資料';
                document.getElementById('modalCampStatus').textContent = button.dataset.campStatus || '無資料';
                document.getElementById('modalCampCreated').textContent = button.dataset.campCreated || '無資料';
                document.getElementById('modalCampUpdated').textContent = button.dataset.campUpdated || '無資料';
                document.getElementById('modalCampDescription').textContent = button.dataset.campDescription || '無資料';
                document.getElementById('modalCampRules').textContent = button.dataset.campRules || '無資料';
                document.getElementById('modalCampNotice').textContent = button.dataset.campNotice || '無資料';
                document.getElementById('modalCampComment').textContent = button.dataset.campComment || '無審核意見';
            });
        }
    });

    // 添加篩選功能
    function filterCamps(type) {
        const campRows = document.querySelectorAll('.spot-list tbody tr');

        campRows.forEach(row => {
            const statusText = row.querySelector('.status-badge').textContent.trim();

            switch (type) {
                case 'pending':
                    row.style.display = statusText === '審核中' ? '' : 'none';
                    break;
                case 'approved':
                    row.style.display = statusText === '已通過' ? '' : 'none';
                    break;
                case 'rejected':
                    row.style.display = statusText === '已退回' ? '' : 'none';
                    break;
                default:
                    row.style.display = '';
            }
        });
    }
</script>

<style>
    :root {
        --camp-primary: #4C6B74;
        --camp-primary-dark: #3A545C;
        --camp-secondary: #94A7AE;
        --camp-light: #F5F7F8;
        --camp-border: #E3E8EA;
        --camp-text: #2A4146;
        --camp-warning: #B4A197;
        --camp-warning-dark: #9B8A81;
        --camp-danger: #B47B84;
    }

    body {
        background-color: var(--camp-light);
        color: var(--camp-text);
        min-height: 100vh;
        padding: 1rem 1rem 1rem 260px;
    }

    .page-container {
        max-width: 1600px;
        margin: 60px 100px 100px;
        padding: 2rem;
        background-color: white;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    }

    /* 更新表格相關樣式 */
    .spot-list {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .spot-list th {
        padding: 1rem;
        background-color: var(--camp-primary);
        color: white;
        font-weight: 500;
    }

    .spot-list th:first-child {
        border-radius: 8px 0 0 8px;
    }

    .spot-list th:last-child {
        border-radius: 0 8px 8px 0;
    }

    .spot-list td {
        padding: 1.2rem;
        background-color: transparent;
        vertical-align: middle;
        border-bottom: 1px solid var(--camp-border);
    }

    .spot-list tr:last-child td {
        border-bottom: none;
    }

    /* 主要內容區域樣式 */
    .content-wrapper {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    /* 狀態標籤樣式 */
    .status-badge {
        padding: 0.6rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-block;
        margin-bottom: 0.5rem;
    }

    .status-success {
        background-color: var(--camp-light);
        color: var(--camp-primary);
        border: 1px solid var(--camp-primary);
    }

    .status-warning {
        background-color: #F8F6F4;
        color: var(--camp-warning);
        border: 1px solid var(--camp-warning);
    }

    .status-danger {
        background-color: #FFF5F6;
        color: var(--camp-danger);
        border: 1px solid var(--camp-danger);
    }

    .status-secondary {
        background-color: var(--camp-light);
        color: var(--camp-secondary);
        border: 1px solid var(--camp-secondary);
    }

    /* 按鈕樣式 */
    .btn-edit {
        background-color: var(--camp-primary);
        color: white;
        width: 100%;
        margin-bottom: 0.25rem;
    }

    .btn-edit:hover {
        background-color: var(--camp-primary-dark);
        color: white;
    }

    /* 页面标题样式 */
    .page-title {
        color: var(--camp-primary);
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid var(--camp-border);
        position: relative;
    }

    /* Modal 样式更新 */
    .modal-content {
        border-radius: 16px;
        border: none;
    }

    .modal-header {
        background-color: var(--camp-primary);
        color: white;
        border-bottom: none;
    }

    .modal-title {
        color: white;
    }

    .btn-close {
        filter: brightness(0) invert(1);
    }

    .info-label {
        color: var(--camp-primary);
        font-weight: 500;
    }

    .card {
        box-shadow: none;
    }

    /* 新增麵包屑導航樣式 */
    .breadcrumb-nav {
        max-width: 1200px;
        margin: 1rem auto;
        padding: 0.5rem 1rem;
    }

    .breadcrumb-item {
        color: var(--camp-secondary);
    }

    .breadcrumb-item.active {
        color: var(--camp-primary);
    }

    /* 統計卡片樣式 */
    .stats-container {
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 1rem;
        border: 1px solid var(--camp-border);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border-color: var(--camp-primary);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: var(--camp-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--camp-primary);
    }

    .stat-content {
        flex-grow: 1;
    }

    .stat-number {
        font-size: 1.75rem;
        font-weight: 600;
        color: var(--camp-primary);
        margin: 0;
    }

    .stat-label {
        color: var(--camp-secondary);
        margin: 0;
        font-size: 0.875rem;
    }

    /* 添加動畫效果 */
    @keyframes countUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stat-number {
        animation: countUp 0.5s ease-out forwards;
    }

    /* 按鈕基本樣式 */
    .btn-primary {
        background-color: var(--camp-primary);
        border-color: var(--camp-primary);
        color: white;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background-color: var(--camp-primary);
        border-color: var(--camp-primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
    }

    .btn-primary:focus,
    .btn-primary:active {
        background-color: var(--camp-primary-dark) !important;
        border-color: var(--camp-primary-dark) !important;
        color: white !important;
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(76, 107, 116, 0.15) !important;
    }

    .btn-secondary {
        background-color: var(--camp-secondary);
        border-color: var(--camp-secondary);
        color: white;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background-color: var(--camp-secondary);
        border-color: var(--camp-secondary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(148, 167, 174, 0.2);
    }

    .btn-secondary:focus,
    .btn-secondary:active {
        background-color: #7B8E95 !important;
        border-color: #7B8E95 !important;
        color: white !important;
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(148, 167, 174, 0.15) !important;
    }
</style>

<!-- 在表上方添加 -->
<div class="loading-overlay" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">載入中...</span>
    </div>
</div>