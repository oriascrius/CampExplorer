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
        <div class="page-header d-flex justify-content-between align-items-center mb-2">
            <h1 class="page-title m-0">營地狀態</h1>
            <div class="search-box">
                <input type="text"
                    id="searchInput"
                    class="form-control"
                    placeholder="搜尋營地名稱..."
                    onkeyup="handleSearch()">
                <i class="bi bi-search"></i>
            </div>
        </div>
        <div class="card">
            <?php if (empty($camps)): ?>
                <div class="text-center text-muted py-3">
                    <p>還沒有營地資料</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="spot-list">
                        <thead>
                            <tr>
                                <th onclick="sortTable('name')" class="sortable">
                                    營地名稱 <i class="bi bi-arrow-down-up"></i>
                                </th>
                                <th onclick="sortTable('status')" class="sortable">
                                    審核狀態 <i class="bi bi-arrow-down-up"></i>
                                </th>
                                <th onclick="sortTable('created_at')" class="sortable">
                                    申請時間 <i class="bi bi-arrow-down-up"></i>
                                </th>
                                <th onclick="sortTable('updated_at')" class="sortable">
                                    最後更新 <i class="bi bi-arrow-down-up"></i>
                                </th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="campsList">
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
                                        <button type="button"
                                            class="btn btn-outline-info btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#campDetailModal"
                                            data-camp-name="<?= htmlspecialchars($camp['name']) ?>"
                                            data-camp-address="<?= htmlspecialchars($camp['address']) ?>"
                                            data-camp-status="<?= htmlspecialchars($camp['status_text']) ?>"
                                            data-camp-status-code="<?= htmlspecialchars($camp['status']) ?>"
                                            data-camp-created="<?= date('Y-m-d H:i', strtotime($camp['created_at'])) ?>"
                                            data-camp-updated="<?= date('Y-m-d H:i', strtotime($camp['updated_at'])) ?>"
                                            data-camp-description="<?= htmlspecialchars($camp['description']) ?>"
                                            data-camp-rules="<?= htmlspecialchars($camp['rules']) ?>"
                                            data-camp-notice="<?= htmlspecialchars($camp['notice']) ?>">
                                            查看詳情
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- 添加分頁容器 -->
                    <div class="pagination-wrapper mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="pagination-info">
                                顯示第 <span id="startIndex">0</span> 到第 <span id="endIndex">0</span> 筆，共 <span id="totalItems">0</span> 筆資料
                            </div>
                            <ul class="pagination" id="pagination"></ul>
                        </div>
                    </div>
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
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">關閉</button>
            </div>
            <div class="modal-body">
                <!-- 基本資訊區塊 -->
                <div class="detail-section">
                    <h6 class="section-title">基本資訊</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="form-label">營地名稱</label>
                                <div class="camp-name detail-content"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="form-label">地址</label>
                                <div class="camp-address detail-content"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 審核資訊區塊 -->
                <div class="detail-section">
                    <h6 class="section-title">審核資訊</h6>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <div class="detail-item">
                                <label class="form-label">審核狀態</label>
                                <div class="modalCampStatus detail-content"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <label class="form-label">申請時間</label>
                                <div class="camp-created detail-content"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <label class="form-label">最後更新</label>
                                <div class="camp-updated detail-content"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 詳細內容區塊 -->
                <div class="detail-section">
                    <h6 class="section-title">詳細內容</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="detail-item">
                                <label class="form-label">營地描述</label>
                                <div class="camp-description detail-content"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-item">
                                <label class="form-label">營地規則</label>
                                <div class="camp-rules detail-content"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-item">
                                <label class="form-label">注意事項</label>
                                <div class="camp-notice detail-content"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const campDetailModal = document.getElementById('campDetailModal');
        if (!campDetailModal) return;

        campDetailModal.addEventListener('show.bs.modal', function(event) {
            try {
                const button = event.relatedTarget;
                const modal = this;

                // 獲取當前營地的狀態碼
                const statusCode = button.getAttribute('data-camp-status-code');
                const statusText = button.getAttribute('data-camp-status');

                // 根據狀態碼設置對應的樣式和圖標
                let statusClass, statusIcon;
                switch (parseInt(statusCode)) {
                    case 1:
                        statusClass = 'approved';
                        statusIcon = 'bi-check-circle-fill';
                        break;
                    case 2:
                        statusClass = 'rejected';
                        statusIcon = 'bi-x-circle-fill';
                        break;
                    case 0:
                    default:
                        statusClass = 'pending';
                        statusIcon = 'bi-hourglass-split';
                        break;
                }

                // 更新狀態顯示
                const modalCampStatus = modal.querySelector('.modalCampStatus');
                if (modalCampStatus) {
                    // 清除所有可能的狀態類
                    modalCampStatus.classList.remove('pending', 'approved', 'rejected');
                    // 添加狀態類
                    modalCampStatus.classList.add('status-badge', statusClass);
                    modalCampStatus.innerHTML = `
                        <i class="bi ${statusIcon}"></i>
                        ${statusText}
                    `;
                }

                // 更新其他內容
                modal.querySelector('.camp-name').textContent = button.getAttribute('data-camp-name') || '';
                modal.querySelector('.camp-address').textContent = button.getAttribute('data-camp-address') || '';
                modal.querySelector('.camp-created').textContent = button.getAttribute('data-camp-created') || '';
                modal.querySelector('.camp-updated').textContent = button.getAttribute('data-camp-updated') || '';
                modal.querySelector('.camp-description').textContent = button.getAttribute('data-camp-description') || '無描述';
                modal.querySelector('.camp-rules').textContent = button.getAttribute('data-camp-rules') || '無規則';
                modal.querySelector('.camp-notice').textContent = button.getAttribute('data-camp-notice') || '無注意事項';

            } catch (error) {
                console.error('Modal 更新出錯:', error);
            }
        });

        // Modal 關閉時清除內容
        campDetailModal.addEventListener('hidden.bs.modal', function() {
            const elements = {
                name: this.querySelector('.camp-name'),
                address: this.querySelector('.camp-address'),
                status: this.querySelector('.modalCampStatus'),
                created: this.querySelector('.camp-created'),
                updated: this.querySelector('.camp-updated'),
                description: this.querySelector('.camp-description'),
                rules: this.querySelector('.camp-rules'),
                notice: this.querySelector('.camp-notice')
            };

            // 清除所有內容
            Object.values(elements).forEach(element => {
                if (element) {
                    if (element.classList.contains('status-badge')) {
                        // 清除狀態標籤的所有類別和內容
                        element.className = 'modalCampStatus';
                        element.innerHTML = '';
                    } else {
                        element.textContent = '';
                    }
                }
            });
        });
    });

    // 添加篩選功能
    function filterCamps(type) {
        // 移除所有卡片的 active 狀態
        document.querySelectorAll('.stat-card').forEach(card => {
            card.classList.remove('active');
        });
        
        // 為當前選中的卡片添加 active 狀態
        document.querySelector(`.stat-card.${type}`).classList.add('active');
        
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
                default: // 'all'
                    row.style.display = '';
                    break;
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
        /* 深點的磚紅色 */
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

    /* 停用按鈕 - 蘭迪粉 */
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
        /* margin-bottom: 1rem;
        padding-bottom: 1rem; */
        /* border-bottom: 3px solid var(--camp-border); */
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

    /* 添加搜尋框樣式 */
    .search-box {
        position: relative;
        width: 300px;
    }

    .search-box input {
        padding-right: 30px;
        border-radius: 20px;
    }

    .search-box i {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--camp-secondary);
    }

    /* 添加排序圖標樣式 */
    th.sortable {
        cursor: pointer;
        user-select: none;
    }

    th.sortable i {
        margin-left: 5px;
        font-size: 0.8em;
    }

    /* 分頁樣式 */
    .pagination-container {
        margin-top: 2rem;
        padding: 1rem;
        background: white;
        border-radius: 8px;
    }

    .pagination {
        margin: 0;
    }

    .page-link {
        color: var(--camp-primary);
        cursor: pointer;
    }

    .page-item.active .page-link {
        background-color: var(--camp-primary);
        border-color: var(--camp-primary);
    }

    /* 分頁容器樣式 */
    .pagination-wrapper {
        padding: 1rem;
        background-color: #fff;
        border-top: 1px solid var(--camp-border);
    }

    .pagination-info {
        color: var(--camp-text);
        font-size: 0.9rem;
    }

    .pagination-info span {
        font-weight: bold;
        color: var(--camp-primary);
    }

    /* 狀態標籤樣式 */
    .modalCampStatus {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
    }

    .modalCampStatus i {
        margin-right: 0.375rem;
    }

    /* 不同狀態的顏色 */
    .modalCampStatus.pending {
        color: var(--status-pending);
        background-color: rgba(196, 166, 135, 0.1);
    }

    .modalCampStatus.approved {
        color: var(--status-confirmed);
        background-color: rgba(143, 169, 119, 0.1);
    }

    .modalCampStatus.rejected {
        color: var(--status-cancelled);
        background-color: rgba(198, 155, 151, 0.1);
    }

    /* Modal 樣式優化 */
    .modal-body {
        padding: 1.5rem;
    }

    .detail-section {
        margin-bottom: 2rem;
        padding: 1.25rem;
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    .detail-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        color: var(--camp-primary);
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--camp-primary);
        font-weight: 600;
    }

    .detail-item {
        margin-bottom: 0.5rem;
    }

    .detail-item .form-label {
        font-weight: 500;
        color: #666;
        margin-bottom: 0.25rem;
    }

    .detail-content {
        padding: 0.5rem;
        background-color: white;
        border-radius: 4px;
        min-height: 2.5rem;
        border: 1px solid #dee2e6;
        display: flex;
        align-items: center;
    }

    /* 狀態標籤在 Modal 中的特殊樣式 */
    .modalCampStatus.status-badge {
        display: inline-flex;
        margin: 0.25rem 0;
    }

    /* Modal 標題樣式 */
    .modal-header {
        background: linear-gradient(to right, var(--camp-primary), var(--camp-secondary));
        color: white;
    }

    .modal-header .btn-outline-light {
        color: var(--camp-primary);
        border-color: var(--camp-primary);
    }

    .modal-header .btn-outline-light:hover {
        background-color: var(--camp-primary);
        color: white;
    }

    /* 滾動條美化 */
    .modal-body {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }

    .modal-body::-webkit-scrollbar {
        width: 8px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Modal 中的狀態標籤樣式 */
    .modal .status-badge {
        max-width: 120px;
        padding: 0.5rem 1.2rem;
        margin-left: 0.2rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        color: white;
    }

    /* 審核中 - 莫蘭迪黃色系 */
    .modal .status-badge.pending {
        background-color: #D4C5A9;
        border: 1px solid #C4B699;
    }

    .modal .status-badge.pending i {
        color: #FFF4E6;
    }

    /* 已通過 - 莫蘭迪綠色系 */
    .modal .status-badge.approved {
        background-color: #A8C2B3;
        border: 1px solid #98B2A3;
    }

    .modal .status-badge.approved i {
        color: #E6FCF5;
    }

    /* 已退回 - 莫蘭迪粉色系 */
    .modal .status-badge.rejected {
        background-color: #D4B5B5;
        border: 1px solid #C4A5A5;
    }

    .modal .status-badge.rejected i {
        color: #FFF5F5;
    }

    /* 狀態標籤 hover 效果 */
    .modal .status-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* 圖標樣式 */
    .modal .status-badge i {
        font-size: 1rem;
    }

    /* 確保狀態標籤在 Modal 中的位置正確 */
    .modal .detail-content .status-badge {
        display: inline-flex;
        margin: 0;
    }

    /* 審核資訊區塊樣式 */
    .detail-section {
        margin-bottom: 2rem;
        padding: 1.25rem;
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    /* 調整行高和對齊方式 */
    .detail-section .row {
        min-height: 60px;
        /* 確保每行有固定最小高度 */
    }

    /* 調整垂直對齊 */
    .detail-item {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        /* 垂直置中 */
    }

    /* 調整標籤和內容的間距 */
    .detail-item .form-label {
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #666;
    }

    /* 調整內容區域的對齊 */
    .detail-content {
        padding: 0.5rem;
        background-color: white;
        border-radius: 4px;
        min-height: 2.5rem;
        border: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        /* 垂直置中 */
    }

    /* 特別處理狀態標籤容器 */
    .detail-content .status-badge {
        margin: 0;
    }

    .search-box input {
        padding-right: 35px;
        padding-left: 15px;
        border-radius: 20px;
        border: 1px solid var(--camp-border);
        height: 42px;
        font-size: 0.95rem;
    }

    /* 添加 active 狀態的樣式 */
    .stat-card.active {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    /* 為每種類型的卡片添加 active 狀態樣式 */
    .stat-card.all.active {
        background: linear-gradient(135deg, #A8B9C7 0%, #C4D3DF 100%);
    }

    .stat-card.pending.active {
        background: linear-gradient(135deg, #D4C5A9 0%, #E5DBC8 100%);
    }

    .stat-card.approved.active {
        background: linear-gradient(135deg, #A8C2B3 0%, #C4D8CD 100%);
    }

    .stat-card.rejected.active {
        background: linear-gradient(135deg, #D4B5B5 0%, #E5CDCD 100%);
    }

    /* active 狀態下的文字和圖標顏色 */
    .stat-card.active .stat-icon,
    .stat-card.active .stat-number,
    .stat-card.active .stat-label {
        color: white;
    }

    .stat-card.active .stat-icon {
        background-color: rgba(255, 255, 255, 0.2);
    }
</style>

<!-- 在表上方添加 -->
<div class="loading-overlay" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">載入中...</span>
    </div>
</div>

<script>
    let allCamps = <?= json_encode($camps) ?>;
    let filteredCamps = [...allCamps];
    let currentPage = 1;
    const itemsPerPage = 10;
    let currentSort = {
        field: 'created_at',
        direction: 'desc'
    };

    // 添加 renderCamps 函數
    function renderCamps(camps) {
        const tbody = document.getElementById('campsList');
        if (!tbody) return;

        tbody.innerHTML = '';

        camps.forEach(camp => {
            const row = document.createElement('tr');
            row.innerHTML = `
            <td>${htmlEscape(camp.name)}</td>
            <td>
                <span class="status-badge ${getStatusClass(camp.status)}">
                    <i class="bi ${getStatusIcon(camp.status)}"></i>
                    ${camp.status_text}
                </span>
            </td>
            <td>${formatDate(camp.created_at)}</td>
            <td>${formatDate(camp.updated_at)}</td>
            <td>
                <button type="button" 
                        class="btn btn-outline-info btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#campDetailModal"
                        data-camp-name="${htmlEscape(camp.name)}"
                        data-camp-address="${htmlEscape(camp.address)}"
                        data-camp-status="${htmlEscape(camp.status_text)}"
                        data-camp-status-code="${camp.status}"
                        data-camp-created="${formatDate(camp.created_at)}"
                        data-camp-updated="${formatDate(camp.updated_at)}"
                        data-camp-description="${htmlEscape(camp.description)}"
                        data-camp-rules="${htmlEscape(camp.rules)}"
                        data-camp-notice="${htmlEscape(camp.notice)}">
                    查看詳情
                </button>
            </td>
        `;
            tbody.appendChild(row);
        });
    }

    // 添加輔助函數
    function htmlEscape(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(dateStr) {
        return new Date(dateStr).toLocaleString('zh-TW', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function getStatusClass(status) {
        return {
            0: 'pending',
            1: 'approved',
            2: 'rejected'
        } [status] || 'pending';
    }

    function getStatusIcon(status) {
        return {
            0: 'bi-hourglass-split',
            1: 'bi-check-circle-fill',
            2: 'bi-x-circle-fill'
        } [status] || 'bi-hourglass-split';
    }

    // 搜尋功能
    function handleSearch() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        filteredCamps = allCamps.filter(camp =>
            camp.name.toLowerCase().includes(searchTerm) ||
            camp.address.toLowerCase().includes(searchTerm)
        );
        currentPage = 1;
        updatePagination();
        renderCamps(getCurrentPageCamps());
    }

    // 排序功能
    function sortTable(field) {
        if (currentSort.field === field) {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.field = field;
            currentSort.direction = 'asc';
        }

        // 更新序圖標
        document.querySelectorAll('th.sortable i').forEach(icon => {
            icon.className = 'bi bi-arrow-down-up';
        });
        const currentIcon = document.querySelector(`th[onclick="sortTable('${field}')"] i`);
        currentIcon.className = `bi bi-arrow-${currentSort.direction === 'asc' ? 'up' : 'down'}`;

        // 序數據
        filteredCamps.sort((a, b) => {
            let compareA = a[field];
            let compareB = b[field];

            // 日期類型特殊處理
            if (field.includes('_at')) {
                compareA = new Date(compareA);
                compareB = new Date(compareB);
            }

            if (compareA < compareB) return currentSort.direction === 'asc' ? -1 : 1;
            if (compareA > compareB) return currentSort.direction === 'asc' ? 1 : -1;
            return 0;
        });

        renderCamps(getCurrentPageCamps());
    }

    // 獲取當前頁數據
    function getCurrentPageCamps() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        return filteredCamps.slice(startIndex, startIndex + itemsPerPage);
    }

    // 更新分頁控制項
    function updatePagination() {
        const startIndexEl = document.getElementById('startIndex');
        const endIndexEl = document.getElementById('endIndex');
        const totalItemsEl = document.getElementById('totalItems');

        // 檢查必要的元素是否存在
        if (!startIndexEl || !endIndexEl || !totalItemsEl) {
            console.error('找不到分頁資訊元素');
            return;
        }

        const totalPages = Math.ceil(filteredCamps.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage + 1;
        const endIndex = Math.min(startIndex + itemsPerPage - 1, filteredCamps.length);

        startIndexEl.textContent = filteredCamps.length ? startIndex : 0;
        endIndexEl.textContent = endIndex;
        totalItemsEl.textContent = filteredCamps.length;

        renderPagination(totalPages);
    }

    // 渲染分頁按鈕
    function renderPagination(totalPages) {
        const pagination = document.getElementById('pagination');
        if (!pagination) {
            console.error('找不到分頁容器元');
            return;
        }

        pagination.innerHTML = '';

        // 上一頁
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `
        <a class="page-link" onclick="changePage(${currentPage - 1})">
            <i class="bi bi-chevron-left"></i>
        </a>
    `;
        pagination.appendChild(prevLi);

        // 頁碼
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" onclick="changePage(${i})">${i}</a>`;
                pagination.appendChild(li);
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                const li = document.createElement('li');
                li.className = 'page-item disabled';
                li.innerHTML = '<span class="page-link">...</span>';
                pagination.appendChild(li);
            }
        }

        // 下一頁
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `
        <a class="page-link" onclick="changePage(${currentPage + 1})">
            <i class="bi bi-chevron-right"></i>
        </a>
    `;
        pagination.appendChild(nextLi);
    }

    // 換頁
    function changePage(page) {
        if (page < 1 || page > Math.ceil(filteredCamps.length / itemsPerPage)) return;
        currentPage = page;
        updatePagination();
        renderCamps(getCurrentPageCamps());
    }

    // 確保 DOM 完全載入後再初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 檢查必要的元素是否存在
        const requiredElements = ['startIndex', 'endIndex', 'totalItems', 'pagination', 'campsList'];
        const missingElements = requiredElements.filter(id => !document.getElementById(id));

        if (missingElements.length > 0) {
            console.error('缺少必要的元素:', missingElements);
            return;
        }

        updatePagination();
        renderCamps(getCurrentPageCamps());

        // 預設選中 "全部" 卡片
        document.querySelector('.stat-card.all').classList.add('active');
    });
</script>