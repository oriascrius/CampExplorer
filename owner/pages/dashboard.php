<?php
require_once __DIR__ . '/../../camping_db.php';
?>

<!-- 主要內容區域 -->
<div class="content">
    <!-- 頁面標題區 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="welcome-title">
                <i class="bi bi-house-heart me-2"></i>
                歡迎回來，<?= htmlspecialchars($_SESSION['owner_name'] ?? '') ?>
            </h2>
            <small class="text-muted">營地數據即時監控</small>
        </div>
        <div class="dashboard-tools">
            <span class="date-display me-3">
                <i class="bi bi-clock me-1"></i><?= date('Y-m-d H:i') ?>
            </span>
            <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> 更新數據
            </button>
        </div>
    </div>
    
    <!-- 統計卡片區域 -->
    <div class="row g-4" id="statsContainer">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">載入中...</span>
            </div>
            <div class="mt-2">載入數據中...</div>
        </div>
    </div>

    <!-- 在原有統計卡片後添加圖表區域 -->
    <div class="row mt-4">
        <!-- 訂單趨勢圖 -->
        <div class="col-md-8">
            <div class="stats-card">
                <h5 class="card-title">訂單趨勢</h5>
                <canvas id="orderTrendChart"></canvas>
            </div>
        </div>
        
        <!-- 營位使用率 -->
        <div class="col-md-4">
            <div class="stats-card">
                <h5 class="card-title">營位使用率</h5>
                <canvas id="spotUsageChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 營收分析區域 -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="stats-card">
                <h5 class="card-title">每月營收比較</h5>
                <canvas id="revenueCompareChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stats-card">
                <h5 class="card-title">熱門營位排行</h5>
                <div id="spotRanking" class="ranking-list"></div>
            </div>
        </div>
    </div>
</div>

<!-- 引入必要的 JS -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- 自定義 CSS -->
<style>
.content {
    padding: 20px;
    margin-left: 250px; /* 配合側邊欄寬度 */
    transition: margin-left 0.3s;
}

.stats-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.stats-card:hover {
    transform: translateY(-5px);
}

:root {
    --morandi-green: #94A684;
    --morandi-beige: #E4E4D0;
    --morandi-brown: #AEC3AE;
    --morandi-light: #F9F9F9;
    --morandi-gray: #808080;
}

.welcome-title {
    color: var(--morandi-brown);
    font-size: 1.8rem;
    margin-bottom: 2rem;
}

.stats-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    color: white;
}

.stats-icon.camp { background-color: var(--morandi-green); }
.stats-icon.orders { background-color: var(--morandi-brown); }
.stats-icon.revenue { background-color: var(--morandi-beige); }

.stats-value {
    font-size: 1.8rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0.5rem 0;
}

.stats-label {
    color: var(--morandi-gray);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.trend-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    background-color: #F0F0F0;
}

.loading-spinner {
    text-align: center;
    padding: 2rem;
}

.ranking-list {
    max-height: 300px;
    overflow-y: auto;
}

.ranking-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.ranking-item .rank {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
}

.ranking-item .name {
    flex: 1;
}

.ranking-item .value {
    color: #666;
}

.card-title {
    margin-bottom: 1.5rem;
    color: var(--bs-gray-700);
}
</style>

<!-- JavaScript 代碼保持不變 -->
<script>
// 在現有代碼前添加以下函數

function getStatusBadgeClass(status) {
    const statusMap = {
        0: 'status-pending',    // 審核中
        1: 'status-confirmed',  // 通過
        2: 'status-cancelled'   // 退回
    };
    return statusMap[status] || 'status-pending';
}

function getStatusText(status) {
    const statusMap = {
        0: '審核中',
        1: '已通過',
        2: '已退回'
    };
    return statusMap[status] || '未知狀態';
}

function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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

// 初始化圖表
let orderTrendChart, spotUsageChart, revenueCompareChart;

// 更新儀表板數據時同時更新圖表
async function updateDashboardCharts(data) {
    if (!data || !data.trends || !data.spots || !data.popular_spots) {
        console.error('數據格式不正確:', data);
        return;
    }

    // 清除舊的圖表
    if (orderTrendChart) orderTrendChart.destroy();
    if (spotUsageChart) spotUsageChart.destroy();
    if (revenueCompareChart) revenueCompareChart.destroy();

    // 訂單趨勢圖
    const orderCtx = document.getElementById('orderTrendChart').getContext('2d');
    orderTrendChart = new Chart(orderCtx, {
        type: 'line',
        data: {
            labels: data.trends.dates,
            datasets: [{
                label: '訂單數量',
                data: data.trends.orders,
                borderColor: '#4CAF50',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // 營位使用率圖
    const spotCtx = document.getElementById('spotUsageChart').getContext('2d');
    spotUsageChart = new Chart(spotCtx, {
        type: 'doughnut',
        data: {
            labels: ['已預訂', '可預訂'],
            datasets: [{
                data: [data.spots.booked, data.spots.available],
                backgroundColor: ['#FF6384', '#36A2EB']
            }]
        }
    });

    // 每月營收比較圖
    const revenueCtx = document.getElementById('revenueCompareChart').getContext('2d');
    revenueCompareChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: data.monthly_revenue.map(item => item.month),
            datasets: [{
                label: '營收金額',
                data: data.monthly_revenue.map(item => item.revenue),
                backgroundColor: 'rgba(148, 166, 132, 0.8)',
                borderColor: 'rgba(148, 166, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // 更新熱門營位排行
    const rankingList = document.getElementById('spotRanking');
    rankingList.innerHTML = data.popular_spots.map((spot, index) => `
        <div class="ranking-item">
            <span class="rank">${index + 1}</span>
            <span class="name">${spot.name}</span>
            <span class="value">${spot.bookings}次預訂</span>
        </div>
    `).join('');
}

// 修改原有的 fetchDashboardStats 函數
async function fetchDashboardStats() {
    try {
        const response = await axios.get('/CampExplorer/owner/api/dashboard/get_stats.php');
        if (response.data.success) {
            const data = response.data.data;
            // 更新原有統計卡片
            updateDashboardStats(data);
            // 更新圖表
            updateDashboardCharts(data);
        }
    } catch (error) {
        console.error('更新數據失敗:', error);
        showToastNotification('錯誤', error.message);
    }
}

// 更新時間顯示
function updateDateTime() {
    const timeDisplay = document.querySelector('.date-display');
    if (timeDisplay) {
        const now = new Date();
        timeDisplay.innerHTML = `<i class="bi bi-clock me-1"></i>${now.toLocaleString('zh-TW')}`;
    }
}

// 使用 SweetAlert2 顯示通知
function showToastNotification(title, message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    Toast.fire({
        icon: 'info',
        title: title,
        text: message
    });
}

// 每60秒更新一次數據
setInterval(fetchDashboardStats, 60000);

// 頁面載入時先執行一次
document.addEventListener('DOMContentLoaded', fetchDashboardStats);

// 在 updateDateTime 函數之前添加
function updateDashboardStats(data) {
    if (!data || !data.orders) {
        console.error('數據格式不正確:', data);
        return;
    }

    // 更新統計卡片
    const container = document.getElementById('statsContainer');
    container.innerHTML = `
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-icon camp">
                    <i class="bi bi-house-heart"></i>
                </div>
                <div class="stats-label">營地狀態</div>
                <div class="stats-value">
                    <span class="badge bg-success">營業中</span>
                </div>
                <div class="trend-indicator">
                    <i class="bi bi-geo-alt"></i>
                    營位數量：${data.spots ? data.spots.total : 0}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-icon orders">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stats-label">本月訂單</div>
                <div class="stats-value">${data.orders.month || 0}</div>
                <div class="trend-indicator">
                    <i class="bi bi-clock"></i>
                    今日：${data.orders.today || 0}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-icon revenue">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stats-label">本月營收</div>
                <div class="stats-value">$${numberWithCommas(data.orders.revenue?.month || 0)}</div>
                <div class="trend-indicator">
                    <i class="bi bi-graph-up"></i>
                    今日：$${numberWithCommas(data.orders.revenue?.today || 0)}
                </div>
            </div>
        </div>
    `;
    
    // 更新時間顯示
    updateDateTime();
}
</script>