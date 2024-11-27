<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['owner_id'])) {
    header('Location: /CampExplorer/owner/login.php');
    exit();
}

$current_page = 'spot_list';  // 添加這行來標記當前頁面

require_once __DIR__ . '/../../../camping_db.php';
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>營位管理</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="/CampExplorer/owner/includes/style.css" rel="stylesheet">
    <link href="/CampExplorer/owner/includes/pages-common.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            /* 莫蘭迪色系 */
            --camp-primary: #4C6B74;
            /* 主要藍綠色 */
            --camp-primary-dark: #3A545C;
            /* 深藍綠色 */
            --camp-secondary: #94A7AE;
            /* 次要灰藍色 */
            --camp-light: #F5F7F8;
            /* 淺灰背景色 */
            --camp-border: #E3E8EA;
            /* 邊框色 */
            --camp-text: #2A4146;
            /* 文字色 */
            --camp-warning: #B4A197;
            /* 警告色：莫蘭迪棕 */
            --camp-warning-dark: #9B8A81;
            /* 深莫蘭迪棕 */
            --camp-danger: #B47B84;
            /* 危險色：莫蘭迪粉 */
        }

        body {
            background-color: var(--camp-light);
            color: var(--camp-text);
            min-height: 100vh;
            padding: 1rem 1rem 1rem 260px;
        }

        .page-container {
            padding: 2rem;
            max-width: 1600px;
            margin: 60px 100px 100px;
        }

        /* 主要內容區域樣式 */
        .content-wrapper {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .spot-list-container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .spot-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .spot-list th {
            background-color: var(--camp-primary);
            color: white;
            padding: 1rem;
            font-weight: 500;
            text-align: left;
            border-bottom: none;
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

        .spot-image {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .spot-image:hover,
        .no-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .badge {
            padding: 0.5em 1em;
            font-weight: 500;
        }

        .badge.bg-primary {
            background-color: var(--camp-primary) !important;
        }

        .badge.bg-success {
            background-color: var(--camp-success) !important;
        }

        .badge.bg-warning {
            background-color: var(--camp-warning) !important;
        }

        .badge.bg-info {
            background-color: var(--camp-info) !important;
        }

        .badge.bg-secondary {
            background-color: var(--camp-secondary) !important;
        }

        .btn-primary {
            background-color: var(--camp-primary);
            border-color: var(--camp-primary);
        }

        .btn-success {
            background-color: var(--camp-success);
            border-color: var(--camp-success);
        }

        .btn-warning {
            background-color: var(--camp-warning);
            border-color: var(--camp-warning);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.375rem 1rem;
            font-weight: 500;
        }

        h2 {
            color: var(--camp-primary);
        }

        .camp-badge-primary {
            background-color: var(--camp-primary) !important;
        }

        .camp-badge-success {
            background-color: var(--camp-success) !important;
        }

        .camp-badge-warning {
            background-color: var(--camp-warning) !important;
        }

        .camp-badge-info {
            background-color: var(--camp-info) !important;
        }

        .camp-badge-secondary {
            background-color: var(--camp-secondary) !important;
        }

        .camp-btn-outline {
            color: var(--camp-outline);
            border: 1px solid var(--camp-outline);
            background-color: transparent;
        }

        .camp-btn-outline:hover {
            color: white;
            background-color: var(--camp-outline);
        }

        .camp-btn-success {
            color: white;
            background-color: var(--camp-success);
            border: none;
        }

        .camp-btn-warning {
            color: white;
            background-color: var(--camp-warning);
            border: none;
        }

        .btn {
            padding: 0.375rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        /* 價格標籤 */
        .price-tag {
            background-color: var(--camp-light);
            color: var(--camp-primary);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: bold;
            display: inline-block;
            border: 2px solid var(--camp-primary);
        }

        /* 狀態標籤 */
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

        .btn-edit:hover:not(:disabled) {
            background-color: var(--camp-primary-dark);
            color: white;
        }

        .btn-disable {
            background-color: var(--camp-warning);
            color: white;
            width: 100%;
        }

        .btn-disable:hover:not(:disabled) {
            background-color: var(--camp-warning-dark);
            color: white;
        }

        .btn-enable {
            background-color: var(--camp-secondary);
            color: white;
            width: 100%;
        }

        .btn-enable:hover:not(:disabled) {
            background-color: #7B8E95;
            color: white;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #CBD5D9;
            border: none;
        }

        .d-flex.flex-column.gap-2 {
            min-width: 100px;
        }

        /* 新增標題樣式 */
        .page-title {
            color: var(--camp-primary);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--camp-border);
            position: relative;
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
            padding: 0.5rem;
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

        .no-image {
            width: 120px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--camp-light);
            border: 2px dashed var(--camp-border);
            border-radius: 8px;
            color: var(--camp-secondary);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .no-image i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="page-container">
        <!-- 統計卡片區域 -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card" onclick="filterSpots('all')">
                        <div class="stat-icon">
                            <i class="bi bi-grid-fill"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" id="totalSpots">0</h3>
                            <p class="stat-label">總營位數</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" onclick="filterSpots('active')">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" id="activeSpots">0</h3>
                            <p class="stat-label">使用中營位</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" onclick="filterSpots('inactive')">
                        <div class="stat-icon">
                            <i class="bi bi-pause-circle-fill"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" id="inactiveSpots">0</h3>
                            <p class="stat-label">已停用營位</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" onclick="filterSpots('pending')">
                        <div class="stat-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" id="pendingSpots">0</h3>
                            <p class="stat-label">審核中營位</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 主要內容區 -->
        <div class="content-wrapper">
            <div class="page-header">
                <h1 class="page-title">營位管理</h1>
            </div>
            <table class="spot-list">
                <thead>
                    <tr>
                        <th>圖片</th>
                        <th>營位名稱</th>
                        <th>容納人數</th>
                        <th>價格</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="spot-list"></tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // 添加快取變數和載入狀態控制
        let spotsCache = null;
        let lastFetchTime = null;
        const CACHE_DURATION = 30000; // 30秒快取
        let isLoading = false;

        // 優化後的 getSpots 函數
        async function getSpots(forceRefresh = false) {
            try {
                if (isLoading) return;

                const now = Date.now();
                if (!forceRefresh && spotsCache && lastFetchTime && (now - lastFetchTime < CACHE_DURATION)) {
                    updateUI(spotsCache);
                    return;
                }

                isLoading = true;

                // 顯示載入中
                const spotList = document.querySelector('#spot-list');
                if (spotList) {
                    spotList.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">載入中...</span>
                                </div>
                                <div class="mt-2">載入營位資料中...</div>
                            </td>
                        </tr>
                    `;
                }

                const response = await axios.get('/CampExplorer/owner/api/spots/get_owner_spots.php');

                if (response.data.success) {
                    spotsCache = response.data.spots;
                    lastFetchTime = now;
                    updateUI(spotsCache);
                } else {
                    throw new Error(response.data.message || '載入失敗');
                }

            } catch (error) {
                console.error('Error:', error);
                const errorMessage = error.response?.data?.message || error.message || '系統錯誤';

                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: errorMessage,
                    confirmButtonColor: '#4C6B74'
                });

                // 顯示錯誤狀態
                const spotList = document.querySelector('#spot-list');
                if (spotList) {
                    spotList.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-4 text-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${errorMessage}
                            </td>
                        </tr>
                    `;
                }
            } finally {
                isLoading = false;
            }
        }

        // 優化錯誤提示
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: '錯誤',
                text: message,
                confirmButtonColor: '#4C6B74',
                timer: 3000,
                timerProgressBar: true
            });
        }

        // 優化 UI 更新邏輯
        function updateUI(spots) {
            requestAnimationFrame(() => {
                // 保存當前的篩狀態
                const activeFilter = document.querySelector('.filter-btn.active');
                const currentFilter = activeFilter ? activeFilter.dataset.filter : 'all';

                // 更新統計和列表
                updateStatistics(spots);
                updateSpotsList(spots);

                // 重新應用篩選
                if (currentFilter !== 'all') {
                    filterSpots(currentFilter);
                }
            });
        }

        // 分離統計更新邏輯
        function updateStatistics(spots) {
            const stats = {
                total: spots.length,
                active: spots.filter(spot => spot.is_active).length,
                inactive: spots.filter(spot => !spot.is_active).length,
                pending: spots.filter(spot => spot.application_status_text === '審核中').length
            };

            Object.entries(stats).forEach(([key, value]) => {
                const element = document.getElementById(`${key}Spots`);
                if (element) {
                    element.textContent = value;
                }
            });
        }

        // 分離列表更新邏輯
        function updateSpotsList(spots) {
            const spotList = document.querySelector('#spot-list');
            if (!spotList) return;

            // 使用 DocumentFragment 優化效能
            const fragment = document.createDocumentFragment();

            spots.forEach(spot => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <img src="${spot.image_path}" 
                             alt="${spot.spot_name}" 
                             class="spot-image"
                             loading="lazy">
                    </td>
                    <td>
                        <div class="fw-bold">${spot.spot_name}</div>
                        <small class="text-muted">${spot.camp_name}</small>
                    </td>
                    <td>${spot.capacity} 人</td>
                    <td>
                        <div class="price-tag">
                            ${spot.price_formatted}
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-2 text-center">
                            <span class="status-badge status-${spot.application_status_class}">
                                ${spot.application_status_text}
                            </span>
                            <span class="status-badge status-${spot.active_status_class}">
                                ${spot.active_status_text}
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-2">
                            <button class="btn btn-sm btn-edit" 
                                    onclick="editSpot(${spot.spot_id})"
                                    ${!spot.can_edit ? 'disabled' : ''}>
                                <i class="fas fa-edit me-1"></i>編輯
                            </button>
                            ${getStatusButton(spot)}
                        </div>
                    </td>
                `;
                fragment.appendChild(tr);
            });

            spotList.innerHTML = '';
            spotList.appendChild(fragment);
        }

        // 輔助函數：取得狀態按鈕 HTML
        function getStatusButton(spot) {
            return spot.is_active ?
                `<button class="btn btn-sm btn-disable" 
                        onclick="toggleSpotStatus(${spot.spot_id}, false)"
                        ${!spot.can_edit ? 'disabled' : ''}>
                    <i class="fas fa-ban me-1"></i>停用
                </button>` :
                `<button class="btn btn-sm btn-enable" 
                        onclick="toggleSpotStatus(${spot.spot_id}, true)"
                        ${!spot.can_edit ? 'disabled' : ''}>
                    <i class="fas fa-check me-1"></i>啟用
                </button>`;
        }

        // 添加自動重新整理機制
        let refreshInterval;

        function startAutoRefresh() {
            refreshInterval = setInterval(() => {
                getSpots(true);
            }, 300000); // 每5分鐘重新整理一次
        }

        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }

        // 頁面載入和切換時的初始化
        document.addEventListener('DOMContentLoaded', initializeSpotList);
        document.addEventListener('contentLoaded', initializeSpotList);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });

        // 初始化函數
        function initializeSpotList() {
            if (document.getElementById('spot-list')) {
                getSpots();
                startAutoRefresh();

                // 添加篩選按鈕事件監聽
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        // 移除所有按鈕的 active 類別
                        document.querySelectorAll('.filter-btn').forEach(b =>
                            b.classList.remove('active'));

                        // 添加當前按鈕的 active 類別
                        e.target.classList.add('active');

                        // 執行篩選
                        filterSpots(e.target.dataset.filter);
                    });
                });
            } else {
                stopAutoRefresh();
            }
        }

        // 添加篩選功能
        function filterSpots(type) {
            const spotRows = document.querySelectorAll('#spot-list tr');

            spotRows.forEach(row => {
                const statusText = row.querySelector('.status-badge').textContent.trim();
                const isActive = !row.querySelector('.btn-enable');

                switch (type) {
                    case 'active':
                        row.style.display = isActive ? '' : 'none';
                        break;
                    case 'inactive':
                        row.style.display = !isActive ? '' : 'none';
                        break;
                    case 'pending':
                        row.style.display = statusText === '審核中' ? '' : 'none';
                        break;
                    default:
                        row.style.display = '';
                }
            });
        }

        // 編輯營位
        async function editSpot(spotId) {
            try {
                // 先獲取營位資料
                const response = await fetch('/CampExplorer/owner/api/spots/get_spot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        spot_id: spotId
                    })
                });

                if (!response.ok) {
                    throw new Error('無法獲取營位資料');
                }

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message);
                }

                // 顯示編輯表單
                const result = await Swal.fire({
                    title: '編輯營位',
                    html: `
                <form id="editSpotForm" class="text-start">
                    <div class="mb-3">
                        <label class="form-label">營位名稱</label>
                        <input type="text" id="spotName" class="form-control" value="${data.spot.name}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">容納人數</label>
                        <input type="number" id="capacity" class="form-control" value="${data.spot.capacity}" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">價格</label>
                        <input type="number" id="price" class="form-control" value="${data.spot.price}" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea id="description" class="form-control" rows="3">${data.spot.description || ''}</textarea>
                    </div>
                </form>
            `,
                    showCancelButton: true,
                    confirmButtonText: '確認修改',
                    cancelButtonText: '取消',
                    confirmButtonColor: '#4C6B74',
                    cancelButtonColor: '#B47B84',
                    focusConfirm: false,
                    preConfirm: () => {
                        const form = document.getElementById('editSpotForm');
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return false;
                        }
                        return {
                            spot_id: spotId,
                            spot_name: document.getElementById('spotName').value,
                            capacity: document.getElementById('capacity').value,
                            price: document.getElementById('price').value,
                            description: document.getElementById('description').value
                        };
                    }
                });

                if (result.isConfirmed) {
                    const updateResponse = await fetch('/CampExplorer/owner/api/spots/update_spot.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(result.value)
                    });

                    const updateData = await updateResponse.json();

                    if (updateData.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: '成功',
                            text: '營位資料已更新',
                            confirmButtonColor: '#4C6B74',
                            timer: 1500,
                            timerProgressBar: true
                        });
                        getSpots(true);
                    } else {
                        throw new Error(updateData.message);
                    }
                }
            } catch (error) {
                console.error('編輯營位失敗:', error);
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: error.message || '編輯營位時發生錯誤',
                    confirmButtonColor: '#4C6B74'
                });
            }
        }

        // 切換營位狀態（啟用/停用）
        async function toggleSpotStatus(spotId, isActive) {
            try {
                // 先顯示確認對話框
                const result = await Swal.fire({
                    title: '確認操作',
                    text: `確定要${isActive ? '啟用' : '停用'}此營位嗎？`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4C6B74',
                    cancelButtonColor: '#B47B84',
                    confirmButtonText: '確定',
                    cancelButtonText: '取消'
                });

                // 如果用戶取消，則直接返回
                if (!result.isConfirmed) {
                    return;
                }

                const formData = new FormData();
                formData.append('spot_id', spotId);
                formData.append('is_active', isActive ? '1' : '0');

                const response = await fetch('/CampExplorer/owner/api/spots/toggle_spot_status.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    await Swal.fire({
                        title: '成功',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#4C6B74',
                        timer: 1500,
                        timerProgressBar: true
                    });
                    // 重新載入營位列表
                    getSpots(true);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('更新狀態失敗:', error);
                Swal.fire({
                    title: '錯誤',
                    text: error.message || '更新狀態時發生錯誤',
                    icon: 'error',
                    confirmButtonColor: '#4C6B74',
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        }
    </script>
</body>

</html>