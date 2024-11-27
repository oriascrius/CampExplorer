<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['owner_id'])) {
    header('Location: /CampExplorer/owner/login.php');
    exit();
}

$current_page = 'booking_list';
require_once __DIR__ . '/../../../camping_db.php';
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單管理</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="/CampExplorer/owner/includes/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        /* 莫蘭迪色系定義 */
        :root {
            --morandy-blue: #94A7AE;
            --morandy-green: #4C6B74;
            --morandy-dark: #3A545C;
            --morandy-light: #F5F7F8;
            --morandy-border: #E3E8EA;
            --morandy-text: #2A4146;
            --morandy-warning: #B4A197;
            --morandy-danger: #B47B84;
            --camp-primary: #4C6B74;
            --camp-border: #E3E8EA;
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
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid var(--morandy-border);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--morandy-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--morandy-green);
        }

        .stat-content {
            flex-grow: 1;
        }

        .stat-number {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--morandy-green);
            margin: 0;
        }

        .stat-label {
            color: var(--morandy-text);
            margin: 0;
            font-size: 0.875rem;
        }

        /* 訂單表格樣式 */
        .booking-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            border-spacing: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .booking-table th {
            background: var(--morandy-green);
            color: white;
            padding: 1rem;
            text-align: left;
        }

        .booking-table th:first-child {
            border-radius: 8px 0 0 0;
        }

        .booking-table th:last-child {
            border-radius: 0 8px 0 0;
        }

        .booking-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--morandy-border);
            vertical-align: middle;
        }

        .booking-table tr:hover {
            background-color: var(--morandy-light);
        }

        /* 狀態標籤 */
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            color: white;
            display: inline-block;
        }

        .status-pending {
            background-color: var(--morandy-warning);
        }

        .status-confirmed {
            background-color: var(--morandy-green);
        }

        .status-cancelled {
            background-color: var(--morandy-danger);
        }

        /* 表格樣式優化 */
        .booking-table td {
            white-space: nowrap;  /* 防止文字換行 */
            max-width: 200px;     /* 最大寬度 */
            overflow: hidden;     /* 超出隱藏 */
            text-overflow: ellipsis; /* 顯示省略號 */
        }

        /* 價格欄位靠右對齊 */
        .booking-table td:nth-child(6),
        .booking-table td:nth-child(7) {
            text-align: right;
        }

        /* 數量欄位置中對齊 */
        .booking-table td:nth-child(5) {
            text-align: center;
        }

        /* 狀態標籤置中 */
        .booking-table td:nth-child(9) {
            text-align: center;
        }

        /* 表格懸停效果 */
        .booking-table tbody tr:hover {
            background-color: var(--morandy-light);
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="page-container" style="margin-left: 360px;">
        <!-- 統計卡片 -->
        <div class="row g-4 stats-container">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="totalBookings">0</h3>
                        <p class="stat-label">總訂單數</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="pendingBookings">0</h3>
                        <p class="stat-label">待處理訂單</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="totalRevenue">$0</h3>
                        <p class="stat-label">總營收</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 主要內容區 -->
        <div class="content-wrapper">
            <!-- 標題列 -->
            <div class="d-flex justify-content-between align-items-center" style="
                padding-bottom: 1rem;
                border-bottom: 3px solid var(--camp-border);">
                <h1 class="page-title">訂單管理</h1>
            </div>

            <!-- 訂單列表 -->
            <div class="table-responsive mt-4">
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>訂單編號</th>
                            <th>活動名稱</th>
                            <th>營位名稱</th>
                            <th>預訂者</th>
                            <th>數量</th>
                            <th>單價</th>
                            <th>總價</th>
                            <th>預訂日期</th>
                            <th>狀態</th>
                            <th>建立時間</th>
                            <th>更新時間</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsList"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        async function fetchBookings() {
            try {
                console.log('開始獲取訂單數據...');
                const response = await axios.get('/CampExplorer/owner/api/booking/get_bookings.php');
                console.log('API 響應:', response.data); // 檢查 API 響應

                if (response.data.success) {
                    if (response.data.bookings && response.data.bookings.length > 0) {
                        updateBookingsList(response.data.bookings);
                        updateStats(response.data.stats);
                    } else {
                        document.getElementById('bookingsList').innerHTML = `
                            <tr>
                                <td colspan="11" class="text-center">目前沒有訂單記錄</td>
                            </tr>
                        `;
                    }
                } else {
                    console.error('獲取數據失敗:', response.data.message);
                }
            } catch (error) {
                console.error('API 錯誤:', error);
                document.getElementById('bookingsList').innerHTML = `
                    <tr>
                        <td colspan="11" class="text-center">獲取數據時發生錯誤</td>
                    </tr>
                `;
            }
        }

        function updateBookingsList(bookings) {
            console.log('更新訂單列表，數據:', bookings); // 檢查訂單數據
            const tbody = document.getElementById('bookingsList');
            tbody.innerHTML = '';

            bookings.forEach(booking => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${booking.booking_id || 'N/A'}</td>
                    <td>${booking.activity_name || '未指定活動'}</td>
                    <td>${booking.spot_name || '未指定營位'}</td>
                    <td>${booking.user_name || '未知用戶'}</td>
                    <td>${booking.quantity || '0'}</td>
                    <td>${formatPrice(booking.unit_price || 0)}</td>
                    <td>${formatPrice(booking.total_price || 0)}</td>
                    <td>${formatDate(booking.booking_date) || 'N/A'}</td>
                    <td>${getStatusBadge(booking.status) || 'N/A'}</td>
                    <td>${formatDate(booking.created_at) || 'N/A'}</td>
                    <td>${formatDate(booking.updated_at) || 'N/A'}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // 確保 DOM 載入完成後執行
        document.addEventListener('DOMContentLoaded', () => {
            console.log('頁面載入完成');
            fetchBookings();
        });

        function updateStats(stats) {
            document.getElementById('totalBookings').textContent = stats.total_bookings;
            document.getElementById('pendingBookings').textContent = stats.pending_bookings;
            document.getElementById('totalRevenue').textContent = `$${stats.total_revenue}`;
        }

        function formatPrice(price) {
            return `$${Number(price).toLocaleString('zh-TW')}`;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('zh-TW', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function getStatusBadge(status) {
            const statusMap = {
                'pending': '<span class="status-badge status-pending">待處理</span>',
                'confirmed': '<span class="status-badge status-confirmed">已確認</span>',
                'cancelled': '<span class="status-badge status-cancelled">已取消</span>'
            };
            return statusMap[status] || status;
        }
    </script>
</body>

</html>