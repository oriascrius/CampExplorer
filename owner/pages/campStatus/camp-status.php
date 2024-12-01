<?php
require_once __DIR__ . '/../../../camping_db.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: ../../../owner-login.php");
    exit;
}

$owner_id = $_SESSION['owner_id'];
$camps = [];

try {
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
} catch (PDOException $e) {
    $error = "查詢失敗：" . $e->getMessage();
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
                <div class="stat-card all" onclick="filterCamps('all')">
                    <div class="stat-icon">
                        <i class="fas fa-campground"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['total'] ?></div>
                        <div class="stat-label">總營地數</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card pending" onclick="filterCamps('pending')">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['pending'] ?></div>
                        <p class="stat-label">審核中</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card approved" onclick="filterCamps('approved')">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['approved'] ?></div>
                        <p class="stat-label">已通過</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card rejected" onclick="filterCamps('rejected')">
                    <div class="stat-icon">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['rejected'] ?></div>
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
                                        <span class="status-badge <?= match ($camp['status']) {
                                                                        0 => 'pending',
                                                                        1 => 'approved',
                                                                        2 => 'rejected',
                                                                        default => 'pending'
                                                                    } ?>">
                                            <i class="bi <?= match ($camp['status']) {
                                                                0 => 'bi-hourglass-split',
                                                                1 => 'bi-check-circle-fill',
                                                                2 => 'bi-x-circle-fill',
                                                                default => 'bi-hourglass-split'
                                                            } ?>"></i>
                                            <?= $camp['status_text'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($camp['created_at'])) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($camp['updated_at'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-outline-info btn-sm"
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
<div class="modal fade"
    id="campDetailModal"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="campDetailModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="campDetailModalLabel">營地詳細資訊</h5>
                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="關閉">
                </button>
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
                <button type="button"
                    class="btn btn-outline-info"
                    data-bs-dismiss="modal"
                    aria-label="關閉對話框">關閉</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 表格行點件
        document.querySelectorAll('table tbody tr').forEach(row => {
            row.addEventListener('click', function(e) {
                // 如果點擊的是按鈕本身，不需要發行點擊事件
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
            // Modal 開啟時的處理
            campDetailModal.addEventListener('show.bs.modal', function(event) {
                // 移除 aria-hidden 屬性
                this.removeAttribute('aria-hidden');

                const button = event.relatedTarget;

                // 更新 Modal 內容
                document.getElementById('modalCampName').textContent = button.dataset.campName || '無資料';
                document.getElementById('modalCampAddress').textContent = button.dataset.campAddress || '無資料';
                document.getElementById('modalCampStatus').textContent = button.dataset.campStatus || '無資料';
                document.getElementById('modalCampCreated').textContent = button.dataset.campCreated || '無資料';
                document.getElementById('modalCampUpdated').textContent = button.dataset.campUpdated || '無資料';
                document.getElementById('modalCampDescription').textContent = button.dataset.campDescription || '無資料';
                document.getElementById('modalCampRules').textContent = button.dataset.campRules || '無資料';
                document.getElementById('modalCampNotice').textContent = button.dataset.campNotice || '無資料';
                document.getElementById('modalCampComment').textContent = button.dataset.campComment || '無核意見';
            });

            // Modal 關閉時的處理
            campDetailModal.addEventListener('hide.bs.modal', function() {
                // 使用 inert 屬性而不是 aria-hidden
                this.setAttribute('inert', '');
            });

            // Modal 完全關後的處理
            campDetailModal.addEventListener('hidden.bs.modal', function() {
                // 移除 inert 屬性
                this.removeAttribute('inert');
                // 重置焦點到觸發按鈕
                const triggerButton = document.querySelector('[data-bs-target="#campDetailModal"]');
                if (triggerButton) {
                    triggerButton.focus();
                }
            });
        }

        animateStatNumbers();
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

    // 數字動畫效果
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value.toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // 數字動畫效果
    function animateStatNumbers() {
        const elements = document.querySelectorAll('.stat-number');
        elements.forEach(element => {
            const endValue = parseInt(element.textContent);
            animateValue(element, 0, endValue, 1000);
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

    /* 狀態標籤基本樣式 */
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        color: white;
    }

    /* 審核中 - 莫蘭迪黃 */
    .status-badge.pending {
        background-color: #D4C5A9;
    }

    /* 已通過 - 莫蘭迪綠 */
    .status-badge.approved {
        background-color: #A8C2B3;
    }

    /* 已退回 - 莫蘭迪粉 */
    .status-badge.rejected {
        background-color: #D4B5B5;
    }

    /* hover 效果 */
    .status-badge:hover {
        transform: translateY(-2px);
        filter: brightness(1.05);
    }

    /* 狀態圖標 */
    .status-badge i {
        font-size: 1rem;
    }

    /* 狀態卡片樣式對應更新 */
    .stat-card[onclick="filterCamps('pending')"] .stat-icon {
        background-color: #FFF4E6;
        color: #F76707;
    }

    .stat-card[onclick="filterCamps('approved')"] .stat-icon {
        background-color: #E6FCF5;
        color: #0CA678;
    }

    .stat-card[onclick="filterCamps('rejected')"] .stat-icon {
        background-color: #FFF5F5;
        color: #FA5252;
    }

    /* hover 效果加強 */
    .stat-card:hover .stat-icon {
        background-color: transparent;
        color: white;
    }

    .stat-card:hover .stat-number,
    .stat-card:hover .stat-label {
        color: white;
    }

    /* 狀態標籤 hover 效果 */
    .status-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .status-0:hover {
        background-color: #7B8E95;
        /* 深一點的藍灰色 */
    }

    .status-1:hover {
        background-color: #3A545C;
        /* 深一點的藍綠色 */
    }

    .status-2:hover {
        background-color: #9B6A72;
        /* 深一點的磚紅色 */
    }

    /* 操作按鈕基本樣式 */
    .btn-action {
        padding: 0.4rem 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
        transition: all 0.3s ease;
        background-color: white;
    }

    /* 編輯按鈕 - 莫蘭迪藍 */
    .btn-edit {
        border: 1px solid var(--camp-primary);
        color: var(--camp-primary);
    }

    .btn-edit:hover {
        background-color: var(--camp-primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
    }

    /* 啟用按鈕 - 莫蘭迪綠 */
    .btn-activate {
        border: 1px solid #A8C2B3;
        color: #A8C2B3;
    }

    .btn-activate:hover {
        background-color: #A8C2B3;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(168, 194, 179, 0.2);
    }

    /* 停用按鈕 - 莫蘭迪粉 */
    .btn-deactivate {
        border: 1px solid #B47B84;
        color: #B47B84;
    }

    .btn-deactivate:hover {
        background-color: #B47B84;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(180, 123, 132, 0.2);
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
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--camp-primary) 0%, var(--camp-primary-dark) 100%);
        color: white;
        border-radius: 16px 16px 0 0;
        padding: 1.5rem;
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
        display: grid;
        gap: 1rem;
        padding: 1.5rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
    }

    /* 統計卡片基本樣式 */
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 0.5rem;
        border: 1px solid #E8E8E8;
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 1.8rem;
        position: relative;
        overflow: hidden;
    }

    /* 總營地數 - 莫蘭迪藍 */
    .stat-card.all {
        border-left: 4px solid #A8B9C7;
    }

    .stat-card.all .stat-icon {
        background-color: #EDF2F7;
        color: #A8B9C7;
    }

    .stat-card.all:hover {
        background: linear-gradient(135deg, #A8B9C7 0%, #C4D3DF 100%);
    }

    /* 審核中 - 莫蘭迪黃 */
    .stat-card.pending {
        border-left: 4px solid #D4C5A9;
    }

    .stat-card.pending .stat-icon {
        background-color: #F7F4ED;
        color: #D4C5A9;
    }

    .stat-card.pending:hover {
        background: linear-gradient(135deg, #D4C5A9 0%, #E5DBC8 100%);
    }

    /* 已通過 - 莫蘭迪綠 */
    .stat-card.approved {
        border-left: 4px solid #A8C2B3;
    }

    .stat-card.approved .stat-icon {
        background-color: #EDF5F1;
        color: #A8C2B3;
    }

    .stat-card.approved:hover {
        background: linear-gradient(135deg, #A8C2B3 0%, #C4D8CD 100%);
    }

    /* 已退回 - 莫蘭迪粉 */
    .stat-card.rejected {
        border-left: 4px solid #D4B5B5;
    }

    .stat-card.rejected .stat-icon {
        background-color: #F7EDED;
        color: #D4B5B5;
    }

    .stat-card.rejected:hover {
        background: linear-gradient(135deg, #D4B5B5 0%, #E5CDCD 100%);
    }

    /* 圖標樣式 */
    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }

    /* 文字內容樣式 */
    .stat-content {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: 600;
        margin: 0;
        line-height: 1;
    }

    .stat-label {
        color: #94A3B8;
        margin: 0;
        font-size: 0.9rem;
    }

    /* Hover 效果 */
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    .stat-card:hover .stat-icon {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .stat-card:hover .stat-number,
    .stat-card:hover .stat-label {
        color: white;
    }

    .stat-card:hover::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.1);
        pointer-events: none;
    }

    /* 查看詳情按鈕樣式 */
    .btn-outline-primary {
        color: #94A7AE;
        /* 莫蘭迪灰藍色 */
        background-color: transparent;
        border: 1px solid #94A7AE;
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        color: white;
        background: linear-gradient(135deg, #94A7AE 0%, #B5C4CA 100%);
        border-color: transparent;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(148, 167, 174, 0.2);
    }

    .btn-outline-primary:focus {
        color: white;
        background-color: #94A7AE;
        border-color: #94A7AE;
        box-shadow: 0 0 0 0.25rem rgba(148, 167, 174, 0.25);
    }

    .btn-outline-primary:active {
        color: white;
        background-color: #7B8E95;
        /* 深一點的莫蘭迪灰藍 */
        border-color: #7B8E95;
    }

    /* 查看詳情按鈕 - 莫蘭迪藍色系 */
    .btn-outline-info {
        color: #94A7AE;
        background-color: transparent;
        border: 1px solid #94A7AE;
        transition: all 0.3s ease;
    }

    .btn-outline-info:hover {
        color: white;
        background: linear-gradient(135deg, #94A7AE 0%, #B5C4CA 100%);
        border-color: transparent;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(148, 167, 174, 0.2);
    }

    .btn-outline-info:focus {
        box-shadow: 0 0 0 0.25rem rgba(148, 167, 174, 0.25);
    }

    /* Modal 內容區塊樣式 */
    .modal-body {
        padding: 2rem;
    }

    /* 資訊組樣式 */
    .info-group {
        margin-bottom: 1.5rem;
        padding: 1.2rem;
        border-radius: 12px;
        background-color: var(--camp-light);
        transition: all 0.3s ease;
    }

    .info-group:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 107, 116, 0.1);
    }

    /* 資訊標籤樣式 */
    .info-label {
        color: var(--camp-primary);
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    /* 資訊內容樣式 */
    .info-content {
        color: var(--camp-text);
        font-size: 1rem;
        line-height: 1.6;
        padding: 0.5rem;
        border-radius: 8px;
        background-color: white;
    }

    /* 特殊資訊樣式 */
    .info-highlight {
        background: linear-gradient(135deg, #EDF5F1 0%, #F7F4ED 100%);
        border-left: 4px solid var(--camp-primary);
    }

    /* 互動按鈕樣式 */
    .btn-action {
        color: var(--camp-primary);
        background-color: transparent;
        border: 1px solid var(--camp-primary);
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        margin-right: 0.5rem;
    }

    .btn-action:hover {
        background-color: var(--camp-primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
    }

    /* 統計卡片標籤文字顏色 */
    .stat-card.all .stat-label {
        color: #A8B9C7;
    }

    .stat-card.pending .stat-label {
        color: #D4C5A9;
    }

    .stat-card.approved .stat-label {
        color: #A8C2B3;
    }

    .stat-card.rejected .stat-label {
        color: #D4B5B5;
    }

    /* 數字顏色保持原色 */
    .stat-number {
        color: var(--camp-text);
    }

    /* Hover 時文字變白 */
    .stat-card:hover .stat-number,
    .stat-card:hover .stat-label {
        color: white;
    }

    /* 移除原本的顏色設定 */
    .stat-label {
        font-size: 0.85rem;
        margin: 0;
    }
</style>

<!-- 在表上方添加 -->
<div class="loading-overlay" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">載入中...</span>
    </div>
</div>