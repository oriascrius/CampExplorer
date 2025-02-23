<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['owner_id'])) {
    header('Location: /CampExplorer/owner/login.php');
    exit();
}

$current_page = 'activity_list';
require_once __DIR__ . '/../../../camping_db.php';
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動管理</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="/CampExplorer/owner/includes/style.css" rel="stylesheet">
    <!-- <link href="/CampExplorer/owner/includes/pages-common.css" rel="stylesheet"> -->
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

        /* 活動卡片樣式 */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
            padding: 1rem;
        }

        .activity-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .activity-card:hover {
            transform: translateY(-3px);
        }

        /* 圖片容器 */
        .activity-image {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .activity-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .activity-status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
            backdrop-filter: blur(4px);
            background: rgba(0, 0, 0, 0.6);
        }

        /* 活動內容 */
        .activity-content {
            flex: 1;
            padding: 1rem;
        }

        .activity-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--morandy-text);
            margin-bottom: 0.5rem;
        }

        .activity-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--morandy-dark);
        }

        .info-item i {
            color: var(--morandy-blue);
        }

        /* 操作按鈕 */
        .activity-actions {
            display: flex;
            gap: 0.5rem;
            padding: 0.75rem;
            background: var(--morandy-light);
            border-top: 1px solid var(--morandy-border);
        }

        .btn-action {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            color: white;
        }

        .btn-action:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* 工具提示 */
        .tooltip {
            position: relative;
        }

        .tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.4rem 0.8rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 4px;
            font-size: 0.8rem;
            white-space: nowrap;
            z-index: 1000;
        }

        /* 移除重複的樣式定義 */
        .activity-list td {
            vertical-align: middle;
            padding: 1rem;
        }

        .smaller,
        .small {
            /* 合併相似的字����大小類 */
            font-size: 0.875rem;
        }

        /* 按鈕樣式優化 */
        .btn-action {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            color: white;
        }

        /* 新增按鈕狀態樣式 */
        .btn-action.btn-edit {
            background-color: var(--morandy-blue);
        }

        .btn-action.btn-toggle {
            background-color: var(--morandy-green);
        }

        .btn-action.btn-delete {
            background-color: var(--morandy-danger);
        }

        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* 活動狀態標籤樣式優化 */
        .activity-status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
            backdrop-filter: blur(4px);
            background: rgba(0, 0, 0, 0.6);
        }

        .status-active {
            background: var(--morandy-green) !important;
        }

        .status-inactive {
            background: var(--morandy-warning) !important;
        }

        .status-ended {
            background: var(--morandy-danger) !important;
        }

        .status-pending {
            background: var(--morandy-blue) !important;
        }

        /* 卡片佈局優化 */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
            padding: 1rem;
        }

        .activity-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .activity-content {
            flex: 1;
            padding: 1rem;
        }

        .activity-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin: 1rem auto 1.5rem;
            /* padding: 1rem; */
        }

        .activity-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }



        .activity-table th:first-child {
            border-radius: 8px 0 0 8px;
        }

        .activity-table th:last-child {
            border-radius: 0 8px 8px 0;
        }

        .activity-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--morandy-border);
            vertical-align: middle;
        }

        .activity-table tr:hover {
            background-color: var(--morandy-light);
        }

        /* 活動圖片 */
        .activity-thumbnail {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
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

        /* 操作按鈕 */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.9rem;
        }

        .status-active {
            background-color: var(--morandy-green);
            /* 已上架顏色 */
        }

        .status-inactive {
            background-color: var(--morandy-warning);
            /* 下架中顏色 */
        }

        .swal2-popup {
            width: 600px !important;
        }

        .swal2-html-container {
            margin: 1em 1.6em 0.3em;
        }

        .form-label {
            text-align: left;
            display: block;
            margin-bottom: 0.5rem;
            color: var(--morandy-text);
        }

        .form-control {
            border: 1px solid var(--morandy-border);
            border-radius: 6px;
            padding: 0.5rem;
        }

        .form-control:focus {
            border-color: var(--morandy-green);
            box-shadow: 0 0 0 0.2rem rgba(76, 107, 116, 0.25);
        }

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
            border: 1px solid var(--morandy-border);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-color: var(--morandy-green);
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

        .page-container {
            max-width: 1600px;
            margin: 60px 100px 100px;
            padding: 2rem;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        /* 修改頁面容器樣式 */
        .page-container {
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        /* RWD 調整 */
        @media (max-width: 991px) {
            .page-container {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
        }

        /* 統計卡片容器樣式 */
        .stats-container {
            margin-bottom: 2rem;
        }

        /* 活動表格容器樣式 */
        .activity-table-container {
            background: white;
            border-radius: 12px;
        }

        /* 移除原有的外框容器樣式 */
        .activity-list-container {
            margin: 0;
            padding: 0;
            box-shadow: none;
            background: none;
        }

        /* 更新表格樣式以配合新的布局 */
        .activity-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .activity-table th {
            background-color: var(--morandy-green);
            /* 使用莫蘭迪主色 */
            color: white;
            /* 白色文字 */
            padding: 1rem;
            font-weight: 500;
            text-align: left;
            border-bottom: none;
        }

        .activity-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--morandy-border);
            vertical-align: middle;
        }

        /* 詳細資料視窗樣式 */
        .activity-detail-container {
            padding: 1rem;
            text-align: left;
        }

        .detail-section {
            border-bottom: 1px solid var(--morandy-border);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        /* 表格樣式優化 */
        .activity-detail-container .table {
            font-size: 0.9rem;
        }

        .activity-detail-container .table th {
            background-color: var(--morandy-light);
            color: var(--morandy-text);
            font-weight: 600;
        }

        .activity-detail-container .table td {
            color: var(--morandy-text);
        }

        /* Badge 樣式 */
        .badge {
            padding: 0.5em 1em;
            font-weight: 500;
        }

        .badge.bg-success {
            background-color: var(--morandy-green) !important;
        }

        .badge.bg-warning {
            background-color: var(--morandy-warning) !important;
        }

        .badge.bg-info {
            background-color: var(--morandy-blue) !important;
        }

        .badge.bg-secondary {
            background-color: var(--morandy-secondary) !important;
        }

        /* 圖片容器樣式 */
        .activity-image-container {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            border-radius: 8px;
        }

        /* 滾動條美化 */
        .activity-detail-container ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .activity-detail-container ::-webkit-scrollbar-track {
            background: var(--morandy-light);
            border-radius: 4px;
        }

        .activity-detail-container ::-webkit-scrollbar-thumb {
            background: var(--morandy-secondary);
            border-radius: 4px;
        }

        .activity-detail-container ::-webkit-scrollbar-thumb:hover {
            background: var(--morandy-green);
        }

        /* 操作列樣式 */
        .action-bar {
            margin-bottom: 2rem;
        }

        .action-bar .d-flex {
            justify-content: space-between;
            align-items: center;
        }

        .action-bar .btn-add {
            background-color: var(--morandy-green);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .action-bar .btn-add:hover {
            background-color: var(--morandy-dark);
        }

        /* 操作列樣式 */
        .action-bar {
            margin-bottom: 2rem;
        }

        .btn-add {
            background-color: var(--morandy-green);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            background-color: var(--morandy-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-add i {
            font-size: 0.875rem;
        }

        /* 標題樣式 */
        h2 {
            color: var(--morandy-text);
            font-weight: 600;
            font-size: 1.5rem;
        }

        /* 內容包裝器 */
        .content-wrapper {
            padding: 0 1.5rem;
        }

        /* 標題樣式 */
        .page-title {
            color: var(--camp-primary);
            font-size: 2rem;
            font-weight: 600;
            position: relative;
        }

        /* 操作列樣式 */
        .action-bar {
            padding: 0.5rem 0;
        }

        .btn-add {
            background-color: var(--morandy-green);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            background-color: var(--morandy-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* 內容區塊樣式 */
        .content-wrapper {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        /* 統計卡片容器調整 */
        .stats-container {
            margin-bottom: 2rem;
            padding: 0;
        }

        /* 活動表格容器調整 */
        .activity-table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            /* 確保圓角效果 */
        }

        /* RWD 調整 */
        @media (max-width: 991px) {
            .page-container {
                margin-left: 0;
                padding: 1rem;
            }

            .content-wrapper {
                padding: 1rem;
            }

            .stats-container {
                margin-bottom: 1rem;
            }
        }

        /* 移除衝突的樣式 */
        body {
            background-color: var(--morandy-light);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* 莫蘭迪色系表單樣式 */
        .activity-form-popup {
            border-radius: 16px;
            padding: 1.5rem;
        }

        .activity-form-title {
            color: var(--morandy-text);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .morandy-input,
        .morandy-select,
        .morandy-textarea {
            border: 2px solid var(--morandy-border);
            border-radius: 8px;
            padding: 0.625rem;
            transition: all 0.3s ease;
        }

        .morandy-input:focus,
        .morandy-select:focus,
        .morandy-textarea:focus {
            border-color: var(--morandy-green);
            box-shadow: 0 0 0 0.2rem rgba(76, 107, 116, 0.25);
        }

        .form-label.required::after {
            content: '*';
            color: var(--morandy-danger);
            margin-left: 4px;
        }

        .spot-option {
            background-color: var(--morandy-light);
            border: 1px solid var(--morandy-border);
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .spot-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: var(--morandy-green);
            border-color: var(--morandy-green);
        }

        .btn-primary:hover {
            background-color: var(--morandy-dark);
            border-color: var(--morandy-dark);
        }

        .btn-outline-primary {
            color: var(--morandy-green);
            border-color: var(--morandy-green);
        }

        .btn-outline-primary:hover {
            background-color: var(--morandy-green);
            border-color: var(--morandy-green);
        }

        /* 按鈕樣式 */
        .btn-morandy {
            background-color: var(--morandy-green);
            color: white;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-morandy:hover {
            background-color: var(--morandy-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
        }

        .btn-morandy-outline {
            background-color: transparent;
            color: var(--morandy-green);
            border: 2px solid var(--morandy-green);
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-morandy-outline:hover {
            background-color: var(--morandy-green);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
        }

        /* 圖片上傳樣式 */
        .image-upload-container {
            position: relative;
            width: 100%;
            min-height: 200px;
            border: 2px dashed var(--morandy-border);
            border-radius: 12px;
            background: linear-gradient(to bottom, var(--morandy-light) 0%, rgba(255, 255, 255, 0.8) 100%);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .image-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            cursor: pointer;
            padding: 2rem;
            color: var(--morandy-dark);
        }

        .image-upload-label:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .image-upload-input {
            display: none;
        }

        .image-preview-container {
            position: relative;
            width: 100%;
            padding: 1rem;
            display: flex;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .image-preview-container img {
            width: 100%;
            max-width: 600px;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .image-preview-remove {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 32px;
            height: 32px;
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
        }

        .image-preview-remove:hover {
            background-color: var(--morandy-danger);
            color: white;
            transform: scale(1.1);
        }

        /* 修改統計卡片區域 -->
        <div class="row g-4 stats-container">
            <!-- 全部活動統計卡片 -->
            <div class="col-md-4">
                <div class="stat-card" onclick="filterActivities('all')" style="cursor: pointer;">
                    <div class="stat-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="totalActivities">0</h3>
                        <p class="stat-label">總活動數</p>
                    </div>
                </div>
            </div>
            <!-- 上架中活動統計卡片 -->
            <div class="col-md-4">
                <div class="stat-card" onclick="filterActivities('active')" style="cursor: pointer;">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="activeActivities">0</h3>
                        <p class="stat-label">上架中活動</p>
                    </div>
                </div>
            </div>
            <!-- 下架中活動統計卡片 -->
            <div class="col-md-4">
                <div class="stat-card" onclick="filterActivities('inactive')" style="cursor: pointer;">
                    <div class="stat-icon">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="inactiveActivities">0</h3>
                        <p class="stat-label">下架中活動</p>
                    </div>
                </div>
            </div>
        </div>
        */
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="page-container" style="margin-left: 360px;">
        <!-- 統計卡片 -->
        <div class="stats-container">
            <div class="row g-4 stats-container">
                <!-- 全部活動統計卡片 -->
                <div class="col-md-4">
                    <div class="stat-card" onclick="filterActivities('all')" style="cursor: pointer;">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" id="totalActivities">0</h3>
                            <p class="stat-label">總活動數</p>
                        </div>
                    </div>
                </div>
                <!-- 上架中活動統計卡片 -->
                <div class="col-md-4">
                    <div class="stat-card" onclick="filterActivities('active')" style="cursor: pointer;">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" id="activeActivities">0</h3>
                            <p class="stat-label">上架中活動</p>
                        </div>
                    </div>
                </div>
                <!-- 下架中活動統計卡片 -->
                <div class="col-md-4">
                    <div class="stat-card" onclick="filterActivities('inactive')" style="cursor: pointer;">
                        <div class="stat-icon">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number" id="inactiveActivities">0</h3>
                            <p class="stat-label">下架中活動</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 主要內容區 -->
        <div class="content-wrapper">
            <!-- 操作列 -->
            <div class="action-bar mb-1">
                <div class="d-flex justify-content-between align-items-center" style="
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--camp-border);">
                    <h1 class="page-title">活動管理</h1>
                    <button class="btn btn-add" onclick="showAddActivityForm()">
                        <i class="fas fa-plus me-2"></i>新增活動
                    </button>
                </div>
            </div>

            <!-- 活動列表表格 -->
            <div class="activity-table-container">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>圖片</th>
                            <th>活動名稱</th>
                            <th>營地名稱</th>
                            <th>活動日期</th>
                            <th>價格範圍</th>
                            <th>總數量</th>
                            <th>狀態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="activityTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 新增活動 Modal -->
    <div class="modal fade" id="addActivityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">新增動</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addActivityForm" onsubmit="return false;">
                        <div class="form-group mb-3">
                            <label for="application_id" class="form-label">選擇營地</label>
                            <select name="application_id" class="form-select" required>
                                <option value="">請選擇營地</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">活動名稱</label>
                            <input type="text" name="activity_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">活動標題</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">副標題</label>
                            <input type="text" name="subtitle" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">活動說明</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">注意事項</label>
                            <textarea name="notice" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">開始日期</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">結束日期</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">營位選項</label>
                            <div id="spotOptionsContainer"></div>
                            <button type="button" class="btn btn-outline-primary mt-2" onclick="addSpotOption()">
                                <i class="bi bi-plus-circle me-1"></i>新增營位選項
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="submitActivity()">建立活動</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 載入活動列表
        async function loadActivities() {
            try {
                const response = await axios.get('/CampExplorer/owner/api/activity/get_activities.php');
                if (response.data.success) {
                    const activities = response.data.activities;

                    // 更�����計數據
                    updateStatistics(activities);

                    // 原有的渲染邏輯
                    renderActivities(activities);
                }
            } catch (error) {
                console.error('入活動失敗:', error);
                Swal.fire({
                    icon: 'error',
                    title: '載入失敗',
                    text: error.response?.data?.message || '無法載入活動列表'
                });
            }
        }

        // 格式化日期
        function formatDate(dateString) {
            if (!dateString) return '無日期';
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return '無效日期';
                return date.toLocaleDateString('zh-TW', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                });
            } catch (error) {
                console.error('日期格式化錯誤:', error);
                return '無效日期';
            }
        }

        // 根據狀態取得對應 CSS class
        function getStatusClass(status) {
            if (!status) return 'status-inactive';

            const statusMap = {
                '營地審核中': 'status-pending',
                '營地未通過': 'status-inactive',
                '下架中': 'status-inactive',
                '已結': 'status-ended',
                '即將開始': 'status-active',
                '進行中': 'status-active'
            };
            return statusMap[status] || 'status-inactive';
        }

        // 操作按鈕
        function getActionButtons(activity) {
            const today = new Date().toISOString().split('T')[0];
            const isEditable = activity.start_date > today;
            const canToggle = ['營地審核中', '營地未通過'].indexOf(activity.activity_status) === -1;

            return `
                <button onclick="editActivity(${activity.activity_id})" 
                        class="btn-action btn-edit" 
                        ${!isEditable ? 'disabled' : ''}>
                    <i class="bi bi-pencil"></i>
                </button>
                <button onclick="toggleStatus(${activity.activity_id}, ${activity.is_active})"
                        class="btn-action ${activity.is_active ? 'btn-warning' : 'btn-success'}"
                        ${!canToggle ? 'disabled' : ''}>
                    <i class="bi bi-${activity.is_active ? 'eye-slash' : 'eye'}"></i>
                </button>
                <button onclick="deleteActivity(${activity.activity_id})"
                        class="btn-action btn-danger"
                        ${!isEditable ? 'disabled' : ''}>
                    <i class="bi bi-trash"></i>
                </button>
            `;
        }

        // 新增活動
        function createActivity() {
            const modal = new bootstrap.Modal(document.getElementById('addActivityModal'));

            // 重置表單
            document.getElementById('addActivityForm').reset();
            document.getElementById('spotOptionsContainer').innerHTML = '';

            // 先開啟 Modal
            modal.show();

            // 等待 Modal 完全顯示後再載入資料
            setTimeout(() => {
                loadCampOptions().catch(error => {
                    console.error('載營選項失敗:', error);
                });
            }, 500);
        }

        // 編輯活動函數
        async function editActivity(activityId) {
            try {
                // 獲取活動詳細資料
                const response = await axios.get(`/CampExplorer/owner/api/activity/get_activity.php?id=${activityId}`);

                if (!response.data.success) {
                    throw new Error(response.data.message || '無法獲取活動資料');
                }

                const {
                    activity,
                    spot_options,
                    available_spots
                } = response.data.data;

                // 顯示編輯表單
                const result = await Swal.fire({
                    title: '編輯活動',
                    width: '800px',
                    customClass: {
                        container: 'activity-form-container',
                        popup: 'activity-form-popup'
                    },
                    html: `
                        <form id="editActivityForm" class="needs-validation">
                            <input type="hidden" id="activity_id" value="${activity.activity_id}">
                            <input type="hidden" id="application_id" value="${activity.application_id}">
                            
                            <div class="mb-3">
                                <label class="form-label required">活動名稱</label>
                                <input type="text" id="activity_name" class="form-control" value="${activity.activity_name}" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">活動標題</label>
                                <input type="text" id="title" class="form-control" value="${activity.title}" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">副標題</label>
                                <input type="text" id="subtitle" class="form-control" value="${activity.subtitle || ''}">
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required">開始日期</label>
                                    <input type="date" id="start_date" class="form-control" value="${activity.start_date}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">結束日期</label>
                                    <input type="date" id="end_date" class="form-control" value="${activity.end_date}" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">活動說明</label>
                                <textarea id="description" class="form-control" rows="3">${activity.description || ''}</textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">注意事項</label>
                                <textarea id="notice" class="form-control" rows="3">${activity.notice || ''}</textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">營位選項</label>
                                <div id="spotOptionsContainer">
                                    ${renderSpotOptions(spot_options, available_spots)}
                                </div>
                                <button type="button" class="btn btn-outline-primary mt-2" onclick="addSpotOptionToEdit()">
                                    <i class="bi bi-plus-circle"></i> 新增營位選項
                                </button>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '確認修改',
                    cancelButtonText: '取消',
                    didOpen: () => {
                        // 設置日期輸入限制
                        setupDateInputs();
                        // 初始化營位選項相關功能
                        initializeSpotOptions(available_spots);
                    },
                    preConfirm: async () => {
                        try {
                            return await validateAndSubmitEditForm();
                        } catch (error) {
                            Swal.showValidationMessage(error.message);
                            return false;
                        }
                    }
                });

                if (result.isConfirmed) {
                    await Swal.fire({
                        icon: 'success',
                        title: '修改成功',
                        text: '活動已成功更新',
                        timer: 1500
                    });
                    loadActivities();
                }

            } catch (error) {
                console.error('編輯活動失敗:', error);
                Swal.fire({
                    icon: 'error',
                    title: '編輯活動失敗',
                    text: error.message || '無法編輯活動'
                });
            }
        }

        // 修改驗證和提交表單函數
        async function validateAndSubmitEditForm() {
            try {
                // 基本表單數據
                const formData = {
                    activity_id: document.getElementById('activity_id').value,
                    activity_name: document.getElementById('activity_name').value,
                    title: document.getElementById('title').value,
                    subtitle: document.getElementById('subtitle').value || null,
                    description: document.getElementById('description').value || null,
                    notice: document.getElementById('notice').value || null,
                    start_date: document.getElementById('start_date').value,
                    end_date: document.getElementById('end_date').value
                };

                // 基本欄位驗證
                if (!formData.activity_name?.trim()) throw new Error('請輸入活動名稱');
                if (!formData.title?.trim()) throw new Error('請輸入活動標題');
                if (!formData.start_date) throw new Error('請選擇開始日期');
                if (!formData.end_date) throw new Error('請選擇結束日期');

                // 營位選項驗證
                const container = document.getElementById('spotOptionsContainer');
                const spotOptions = Array.from(container.querySelectorAll('.spot-option'))
                    .map(option => {
                        const select = option.querySelector('.spot-select');
                        const price = option.querySelector('.spot-price');
                        const quantity = option.querySelector('.spot-quantity');

                        if (!select?.value) throw new Error('請選擇營位');
                        if (!price?.value || price.value <= 0) throw new Error('請輸入有效的價格');
                        if (!quantity?.value || quantity.value <= 0) throw new Error('請輸入有效的數量');

                        return {
                            spot_id: select.value,
                            price: price.value,
                            max_quantity: quantity.value
                        };
                    });

                if (spotOptions.length === 0) {
                    throw new Error('請至少設定一個營位選項');
                }

                // 添加營位選項到表單數據
                formData.spot_options = spotOptions;

                // 發送更新請求
                const response = await axios.post('/CampExplorer/owner/api/activity/update_activity.php', formData);
                
                if (!response.data.success) {
                    throw new Error(response.data.message || '更新失敗');
                }

                return true;
            } catch (error) {
                console.error('表單驗證或提交失敗:', error);
                throw error;
            }
        }

        // 修改渲染營位選項函數
        function renderSpotOptions(spotOptions, availableSpots) {
            if (!spotOptions || spotOptions.length === 0) {
                return '<div class="alert alert-info">尚未設定營位選項</div>';
            }

            return spotOptions.map((option, index) => `
                <div class="spot-option mb-2">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select class="form-select spot-select" required>
                                ${availableSpots.map(spot => `
                                    <option value="${spot.spot_id}" 
                                            ${spot.spot_id === option.spot_id ? 'selected' : ''}>
                                        ${spot.spot_name}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control spot-price" 
                                   placeholder="價格" value="${option.price}" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control spot-quantity" 
                                   placeholder="數量" value="${option.max_quantity}" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="removeSpotOptionFromEdit(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // 切換活動狀態
        async function toggleStatus(activityId, currentStatus) {
            try {
                const result = await Swal.fire({
                    title: `確定要${currentStatus ? '下架' : '上架'}此活動？`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '確定',
                    cancelButtonText: '取消',
                    confirmButtonColor: '#4C6B74', // 更加明顯的莫蘭迪綠色
                    cancelButtonColor: '#94A7AE' // 中性的莫蘭迪藍色
                });

                if (result.isConfirmed) {
                    const response = await axios.post('/CampExplorer/owner/api/activity/toggle_status.php', {
                        activity_id: activityId
                    });
                    if (response.data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: '更新成功',
                            text: response.data.message,
                            timer: 1500,
                            confirmButtonColor: '#4C6B74' // 莫蘭迪藍綠色
                        });
                        loadActivities(); // 重新載入活動列表
                    }
                }
            } catch (error) {
                console.error('更新活動狀態失敗:', error);
                Swal.fire({
                    icon: 'error',
                    title: '更新失敗',
                    text: error.response?.data?.message || '無法更新活動態'
                });
            }
        }

        // 刪除活動
        async function deleteActivity(activityId) {
            try {
                const result = await Swal.fire({
                    title: '確定要刪除活動？',
                    text: '此操作無法復原',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '確定刪除',
                    cancelButtonText: '取消',
                    confirmButtonColor: '#d33'
                });

                if (result.isConfirmed) {
                    const response = await axios.post('/CampExplorer/owner/api/activity/delete_activity.php', {
                        activity_id: activityId
                    });

                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '刪除成功',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        loadActivities();
                    }
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: '刪除失敗',
                    text: error.response?.data?.message || '無法刪除動'
                });
            }
        }

        // 新增這個函數來獲取用的營位選項
        function getAvailableSpotOptions() {
            if (!window.availableSpots) return '';
            return window.availableSpots.map(spot =>
                `<option value="${spot.spot_id}" data-max="${spot.max_capacity}">${spot.spot_name}</option>`
            ).join('');
        }

        async function loadCampOptions() {
            try {
                const response = await axios.get('/CampExplorer/owner/api/activity/get_approved_camps.php');

                if (response.data.success) {
                    const campSelect = document.querySelector('#addActivityForm [name="application_id"]');
                    campSelect.innerHTML = '<option value="">請選擇營地</option>';

                    response.data.camps.forEach(camp => {
                        campSelect.innerHTML += `
                            <option value="${camp.application_id}">
                                ${camp.camp_name}
                            </option>`;
                    });
                } else {
                    throw new Error(response.data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: '載入失敗',
                    text: error.response?.data?.message || '無法載入營地列表'
                });
            }
        }

        // 載入已審核通過的營位選項
        async function loadSpotOptions(applicationId) {
            try {
                const response = await axios.get(`/CampExplorer/owner/api/activity/get_approved_spots.php?application_id=${applicationId}`);
                
                if (!response.data.success) {
                    throw new Error(response.data.message || '載入營位失敗');
                }

                window.availableSpots = response.data.spots.map(spot => ({
                    spot_id: spot.spot_id,
                    name: spot.spot_name,
                    max_capacity: spot.max_capacity
                }));

                // 清空並重新添加第一個營位選項
                const container = document.getElementById('spot_options');
                if (container) {
                    container.innerHTML = '';
                    addSpotOption();
                }

            } catch (error) {
                console.error('載入營位選項失敗:', error);
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: error.message || '無法載入營位選項'
                });
            }
        }

        // 更新營位選擇選項
        function updateSpotSelections() {
            const spotSelects = document.querySelectorAll('[name^="spot_options"][name$="[spot_id]"]');
            spotSelects.forEach(select => {
                const currentValue = select.value;
                select.innerHTML = '<option value="">請選擇營位</option>' +
                    window.availableSpots.map(spot =>
                        `<option value="${spot.spot_id}" data-max="${spot.max_capacity}">${spot.spot_name}</option>`
                    ).join('');
                select.value = currentValue;
            });
        }

        // 修改初始化營位選項函數
        function initializeSpotOptions(availableSpots) {
            // 保存可用營位列表到全局變數
            window.availableSpots = availableSpots;

            // 為所有營位選項添加事件監聽
            document.querySelectorAll('.spot-select').forEach(select => {
                select.addEventListener('change', validateSpotSelection);
            });
        }

        // 新增營位選項函數 (用於新增活動表單)
        function addSpotOption() {
            const container = document.getElementById('spot_options');
            if (!container) return;

            // 檢查是否有可用的營位資料
            if (!window.availableSpots || window.availableSpots.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '無法新增',
                    text: '請先選擇營地，或目前營地無可用營位'
                });
                return;
            }

            // 獲取已選擇的營位
            const selectedSpots = Array.from(container.querySelectorAll('select[name^="spot_options"]'))
                .map(select => select.value)
                .filter(value => value);

            // 過濾出未被選擇的營位
            const availableSpots = window.availableSpots.filter(spot => 
                !selectedSpots.includes(spot.spot_id.toString())
            );

            if (availableSpots.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '無法新增',
                    text: '已無可用的營位選項'
                });
                return;
            }

            // 創建新的營位選項
            const newOption = document.createElement('div');
            newOption.className = 'spot-option mb-2';
            newOption.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-4">
                        <select name="spot_options[${selectedSpots.length}][spot_id]" 
                                class="form-select" required>
                            <option value="">選擇營位</option>
                            ${availableSpots.map(spot => `
                                <option value="${spot.spot_id}">${spot.name}</option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" 
                               name="spot_options[${selectedSpots.length}][price]" 
                               class="form-control" 
                               placeholder="價格" 
                               min="0" 
                               required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" 
                               name="spot_options[${selectedSpots.length}][max_quantity]" 
                               class="form-control" 
                               placeholder="數量" 
                               min="1" 
                               required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" 
                                class="btn btn-outline-danger" 
                                onclick="removeSpotOptionFromAdd(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            container.appendChild(newOption);
        }

        // 從新增表單中移除營位選項
        function removeSpotOptionFromAdd(button) {
            const container = document.getElementById('spot_options');
            if (!container) return;

            const options = container.querySelectorAll('.spot-option');
            
            // 如果只剩一個選項，不允許刪除
            if (options.length <= 1) {
                Swal.fire({
                    icon: 'warning',
                    title: '無法刪除',
                    text: '至少需要保留一個營位選項'
                });
                return;
            }

            // 移除選項
            const option = button.closest('.spot-option');
            if (option) {
                option.remove();
                // 重新排序剩餘選項的 name 屬性
                reorderSpotOptions();
            }
        }

        // 重新排序營位選項的 name 屬性
        function reorderSpotOptions() {
            const container = document.getElementById('spot_options');
            if (!container) return;

            const options = container.querySelectorAll('.spot-option');
            options.forEach((option, index) => {
                const select = option.querySelector('select');
                const priceInput = option.querySelector('input[name$="[price]"]');
                const quantityInput = option.querySelector('input[name$="[max_quantity]"]');

                if (select) select.name = `spot_options[${index}][spot_id]`;
                if (priceInput) priceInput.name = `spot_options[${index}][price]`;
                if (quantityInput) quantityInput.name = `spot_options[${index}][max_quantity]`;
            });
        }

        // 修改驗證營位選擇函數
        function validateSpotSelection() {
            const container = document.getElementById('spotOptionsContainer');
            if (!container) return true;

            const selects = container.querySelectorAll('.spot-select');
            const selectedValues = Array.from(selects)
                .map(select => select.value)
                .filter(value => value); // 只考慮有值的選項

            // 檢查是否有選項
            if (selectedValues.length === 0) return true;

            // 檢查重複選擇
            const uniqueValues = new Set(selectedValues);
            if (uniqueValues.size !== selectedValues.length) {
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: '同一個營位不能重複選擇'
                });
                return false;
            }

            return true;
        }

        // 修改獲取營位選項數據函數
        function getSpotOptionsData() {
            const container = document.getElementById('spotOptionsContainer');
            if (!container) return [];

            return Array.from(container.querySelectorAll('.spot-option'))
                .map(option => {
                    const select = option.querySelector('.spot-select');
                    const price = option.querySelector('.spot-price');
                    const quantity = option.querySelector('.spot-quantity');

                    if (!select || !price || !quantity) return null;

                    return {
                        spot_id: select.value,
                        price: price.value,
                        max_quantity: quantity.value
                    };
                })
                .filter(option => 
                    option !== null && 
                    option.spot_id && 
                    option.price && 
                    option.max_quantity
                );
        }

        // 修改營位選項驗證函數
        function validateSpotOption(option) {
            const select = option.querySelector('.spot-select');
            const price = option.querySelector('.spot-price');
            const quantity = option.querySelector('.spot-quantity');

            if (!select?.value) return '請選擇營位';
            if (!price?.value || price.value <= 0) return '請輸入有效的價格';
            if (!quantity?.value || quantity.value <= 0) return '請輸入有效的數量';

            return null;
        }

        // 設置日期輸入限制
        function setupDateInputs() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (!startDateInput || !endDateInput) return;

            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            // 設置最小日期為明天
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            startDateInput.min = tomorrowStr;
            endDateInput.min = tomorrowStr;

            // 當開始日期改變時，更新結束日期的最小值
            startDateInput.addEventListener('change', function() {
                endDateInput.min = this.value;
                if (endDateInput.value && endDateInput.value < this.value) {
                    endDateInput.value = this.value;
                }
            });
        }

        // 提交動表單
        async function submitActivity() {
            try {
                const form = document.getElementById('addActivityForm');
                const formData = new FormData(form);

                // 驗證表單
                if (!validateActivityForm(form)) {
                    return;
                }

                const response = await axios.post('/CampExplorer/owner/api/activity/create_activity.php', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '建立成功',
                        text: response.data.message,
                        showConfirmButton: false,
                        timer: 1500
                    });

                    // 閉 Modal 並重新載入列表
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addActivityModal'));
                    modal.hide();
                    loadActivities();
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: '建立失敗',
                    text: error.response?.data?.message || '無法建立活動'
                });
            }
        }

        // 表單驗證
        function validateActivityForm() {
            // 獲取所有必要的輸入欄位
            const applicationId = document.getElementById('application_id')?.value;
            const activityName = document.getElementById('activity_name')?.value;
            const title = document.getElementById('title')?.value;
            const startDate = document.getElementById('start_date')?.value;
            const endDate = document.getElementById('end_date')?.value;

            // 基本驗證
            if (!applicationId) {
                Swal.showValidationMessage('請選擇營地');
                return false;
            }

            if (!activityName) {
                Swal.showValidationMessage('請輸入活動名稱');
                return false;
            }

            if (!title) {
                Swal.showValidationMessage('請輸入活動標題');
                return false;
            }

            if (!startDate || !endDate) {
                Swal.showValidationMessage('請選擇活動日期');
                return false;
            }

            // 日期驗證
            const startDateTime = new Date(startDate);
            const endDateTime = new Date(endDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (startDateTime <= today) {
                Swal.showValidationMessage('開始日期必須大於今天');
                return false;
            }

            if (endDateTime < startDateTime) {
                Swal.showValidationMessage('結束日期必須大於等於開始日期');
                return false;
            }

            // 營位選項驗證
            const spotOptions = [];
            const spotOptionElements = document.querySelectorAll('.spot-option');

            if (spotOptionElements.length === 0) {
                Swal.showValidationMessage('請至少新增一個營位選項');
                return false;
            }

            spotOptionElements.forEach((option, index) => {
                const spotId = option.querySelector('select[name^="spot_options"][name$="[spot_id]"]')?.value;
                const price = option.querySelector('input[name^="spot_options"][name$="[price]"]')?.value;
                const maxQuantity = option.querySelector('input[name^="spot_options"][name$="[max_quantity]"]')?.value;

                if (!spotId || !price || !maxQuantity) {
                    Swal.showValidationMessage(`請完整填寫營位選項 #${index + 1} 的資訊`);
                    return false;
                }

                if (price <= 0) {
                    Swal.showValidationMessage(`營位選項 #${index + 1} 的價格必須大於 0`);
                    return false;
                }

                if (maxQuantity <= 0) {
                    Swal.showValidationMessage(`營位選項 #${index + 1} 的數量必須大於 0`);
                    return false;
                }

                spotOptions.push({
                    spot_id: spotId,
                    price: parseInt(price),
                    max_quantity: parseInt(maxQuantity)
                });
            });

            return true;
        }

        // 修改初始部分
        document.addEventListener('DOMContentLoaded', () => {
            console.log('頁面載入完成，開始初始化...');
            loadActivities();

            // 當新增活動 Modal 開啟時載入營地選項
            const addActivityModal = document.getElementById('addActivityModal');
            if (addActivityModal) {
                addActivityModal.addEventListener('show.bs.modal', () => {
                    loadCampOptions();
                });
            }

            // 監聽營地選擇變更
            const applicationSelect = document.querySelector('#addActivityForm [name="application_id"]');
            if (applicationSelect) {
                applicationSelect.addEventListener('change', (e) => {
                    const applicationId = e.target.value;
                    if (applicationId) {
                        loadSpotOptions(applicationId);
                    } else {
                        window.availableSpots = [];
                        updateSpotSelections();
                    }
                });
            }
        });

        // 修改渲染活動列表的函數
        function renderActivities(activities) {
            const tbody = document.getElementById('activityTableBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            activities.forEach(activity => {
                // 修改狀態判斷邏輯
                let statusClass = '';
                let statusText = '';

                switch (activity.activity_status) {
                    case '進行中':
                    case '即將開始': // 將 '即將開始' 合併到 '上架中'
                        statusClass = 'status-active';
                        statusText = '上架中';
                        break;
                    case '已結束':
                        statusClass = 'status-ended';
                        statusText = '已結束';
                        break;
                    case '下架中':
                        statusClass = 'status-inactive';
                        statusText = '下架中';
                        break;
                    default:
                        statusClass = 'status-secondary';
                        statusText = activity.activity_status;
                }

                const tr = document.createElement('tr');
                tr.style.cursor = 'pointer';
                // 為整行添加點擊事件，但排除操作按鈕區域
                tr.addEventListener('click', (e) => {
                    // 如果點擊的是按鈕或其父元素按鈕，不觸發詳情視窗
                    if (!e.target.closest('.action-buttons')) {
                        showActivityDetail(activity);
                    }
                });

                tr.innerHTML = `
                    <td>
                        <img src="${activity.main_image ? `/CampExplorer/uploads/activities/${activity.main_image}` : '/CampExplorer/assets/images/no-image.png'}"
                             class="activity-thumbnail"
                             alt="${activity.activity_name}"
                             onerror="this.src='/CampExplorer/assets/images/no-image.png'">
                    </td>
                    <td>
                        <div class="fw-bold">${activity.activity_name}</div>
                        <small class="text-muted">${activity.title || ''}</small>
                    </td>
                    <td>${activity.camp_name}</td>
                    <td>
                        <div>${formatDate(activity.start_date)}~</div>
                        <div>${formatDate(activity.end_date)}</div>
                    </td>
                    <td>
                        <div>NT$ ${Number(activity.min_price).toLocaleString()}</div>
                        ${activity.min_price !== activity.max_price ? 
                            `<small class="text-muted">~ NT$ ${Number(activity.max_price).toLocaleString()}</small>` : ''}
                    </td>
                    <td>${activity.total_quantity || 0}</td>
                    <td>
                        <span class="status-badge ${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-edit" 
                                    onclick="editActivity(${activity.activity_id})"
                                    ${isActivityEditable(activity) ? '' : 'disabled'}>
                                編輯
                            </button>
                            <button class="btn-action btn-toggle"
                                    onclick="toggleStatus(${activity.activity_id}, ${activity.is_active})"
                                    ${isActivityToggleable(activity) ? '' : 'disabled'}>
                                ${activity.is_active ? '下架' : '上架'}
                            </button>
                        </div>
                    </td>
                `;

                tbody.appendChild(tr);
            });
        }

        function isActivityEditable(activity) {
            const notEditableStatus = ['營地審核中', '營地未通過', '進行中', '已結束'];
            return !notEditableStatus.includes(activity.activity_status);
        }

        function isActivityToggleable(activity) {
            const notToggleableStatus = ['營地審核中', '營地未通過', '已結束'];
            return !notToggleableStatus.includes(activity.activity_status);
        }

        function isActivityDeletable(activity) {
            const notDeletableStatus = ['營地審核中', '營地未通過', '進行中', '已結束'];
            return !notDeletableStatus.includes(activity.activity_status);
        }

        // 新增一輔助的 CSS 樣式
        const styles = `
            <style>
                .activity-list td {
                    vertical-align: middle;
                    padding: 1rem;
                }

                .smaller, .small {
                    font-size: 0.875rem;
                }

                .text-muted {
                    color: #6c757d;
                }

                .fw-bold {
                    font-weight: 600;
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);

        // 修改篩選功能
        function filterActivities(type) {
            // 篩選表格內容
            const rows = document.querySelectorAll('#activityTableBody tr');
            rows.forEach(row => {
                const statusBadge = row.querySelector('.status-badge');
                const statusText = statusBadge?.textContent.trim();

                switch (type) {
                    case 'active':
                        row.style.display = (statusText === '上架中') ? '' : 'none';
                        break;
                    case 'inactive':
                        row.style.display = (statusText === '下架中') ? '' : 'none';
                        break;
                    case 'all':
                        row.style.display = '';
                        break;
                }
            });

            // 更新當前篩選狀態
            window.currentFilter = type;
        }

        // 更新統計數據的函數
        function updateStatistics(activities) {
            const stats = {
                total: activities.length,
                active: activities.filter(a =>
                    a.activity_status === '進行中' ||
                    a.activity_status === '即將開始' ||
                    a.is_active === 1
                ).length,
                inactive: activities.filter(a =>
                    a.activity_status === '下架中' ||
                    a.is_active === 0
                ).length
            };

            // 更新統計卡片數據
            document.getElementById('totalActivities').textContent = stats.total;
            document.getElementById('activeActivities').textContent = stats.active;
            document.getElementById('inactiveActivities').textContent = stats.inactive;
        }

        // 在頁面載入時初始化
        document.addEventListener('DOMContentLoaded', () => {
            // 設置初始篩選狀態
            window.currentFilter = 'all';

            // 為統計卡片添加懸停效果
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', () => {
                    if (!card.classList.contains('active')) {
                        card.style.transform = 'translateY(0)';
                    }
                });
            });
        });
  
        // 新增顯示詳細資料的函數
        async function showActivityDetail(activity) {
            // 修改狀態顯示邏輯
            let statusClass = '';
            let statusText = '';

            switch (activity.activity_status) {
                case '進行中':
                case '即將開始':
                    statusClass = 'success';
                    statusText = '上架中';
                    break;
                case '已結束':
                    statusClass = 'secondary';
                    statusText = '已結束';
                    break;
                case '下架中':
                    statusClass = 'warning';
                    statusText = '下架中';
                    break;
                default:
                    statusClass = 'secondary';
                    statusText = activity.activity_status;
            }

            // 格式化日期
            const formatDate = (dateStr) => {
                const date = new Date(dateStr);
                return date.toLocaleDateString('zh-TW');
            };

            // 格化價格
            const formatPrice = (price) => {
                return `NT$ ${Number(price).toLocaleString()}`;
            };

            Swal.fire({
                title: activity.activity_name,
                html: `
                    <div class="activity-detail-container">
                        <!-- 主要資訊區 -->
                        <div class="detail-section mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <img src="${activity.main_image ? `/CampExplorer/uploads/activities/${activity.main_image}` : '/CampExplorer/assets/images/no-image.png'}"
                                         class="img-fluid rounded mb-3"
                                         alt="${activity.activity_name}"
                                         style="width: 100%; height: 200px; object-fit: cover;">
                                </div>
                                <div class="col-md-6 text-start">
                                    <h5 class="mb-3">${activity.title}</h5>
                                    <p class="text-muted mb-2">${activity.subtitle || ''}</p>
                                    <div class="badge bg-${getStatusClass(activity.activity_status)} mb-2">
                                        ${statusText}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 詳細資訊表格 -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody class="text-start">
                                    <tr>
                                        <th width="30%">營地名稱</th>
                                        <td>${activity.camp_name}</td>
                                    </tr>
                                    <tr>
                                        <th>活動期間</th>
                                        <td>${formatDate(activity.start_date)} ~ ${formatDate(activity.end_date)}</td>
                                    </tr>
                                    <tr>
                                        <th>價格範圍</th>
                                        <td>${formatPrice(activity.min_price)} ~ ${formatPrice(activity.max_price)}</td>
                                    </tr>
                                    <tr>
                                        <th>總數量</th>
                                        <td>${activity.total_quantity || 0} 個</td>
                                    </tr>
                                    <tr>
                                        <th>活動說明</th>
                                        <td>${activity.description || '無'}</td>
                                    </tr>
                                    <tr>
                                        <th>注意事項</th>
                                        <td>${activity.notice || '無'}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- 營位選項列表 -->
                        <div class="spot-options mt-4">
                            <h6 class="text-start mb-3">營位選項</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>營位稱</th>
                                            <th>價格</th>
                                            <th>數量</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${activity.spot_options ? activity.spot_options.map(option => `
                                            <tr>
                                                <td>${option.spot_name}</td>
                                                <td>${formatPrice(option.price)}</td>
                                                <td>${option.max_quantity}</td>
                                            </tr>
                                        `).join('') : '<tr><td colspan="3">無營位選項資料</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `,
                width: '800px',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    container: 'activity-detail-modal'
                }
            });
        }

        // 修改 showAddActivityForm 函數
        async function showAddActivityForm() {
            try {
                const campsResponse = await axios.get('/CampExplorer/owner/api/activity/get_approved_camps.php');

                if (!campsResponse.data.success) {
                    throw new Error(campsResponse.data.message || '無法載入營地列表');
                }

                const result = await Swal.fire({
                    title: '新增活動',
                    width: '800px',
                    customClass: {
                        container: 'activity-form-container',
                        popup: 'activity-form-popup',
                        title: 'activity-form-title',
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    html: `
                        <form id="addActivityForm" class="needs-validation">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required">選營地</label>
                                    <select id="application_id" class="form-select morandy-select" required onchange="loadSpotOptions(this.value)">
                                        <option value="">請選擇營地</option>
                                        ${campsResponse.data.camps.map(camp => 
                                            `<option value="${camp.application_id}">${camp.camp_name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">活動名稱</label>
                                    <input type="text" id="activity_name" class="form-control morandy-input" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label required">活動標題</label>
                                    <input type="text" id="title" class="form-control morandy-input" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">副標題</label>
                                    <input type="text" id="subtitle" class="form-control morandy-input">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">開始日期</label>
                                    <input type="date" id="start_date" class="form-control morandy-input" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">結束日期</label>
                                    <input type="date" id="end_date" class="form-control morandy-input" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">活動說明</label>
                                    <textarea id="description" class="form-control morandy-textarea" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">注意事項</label>
                                    <textarea id="notice" class="form-control morandy-textarea" rows="3"></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="image-upload-container">
                                        <input type="file" id="main_image" class="image-upload-input" accept="image/*">
                                        <label for="main_image" class="image-upload-label">
                                            <i class="bi bi-cloud-upload fs-3 mb-2"></i>
                                            <div>點擊或拖曳圖片至此處上傳</div>
                                            <div class="text-muted small">支援 JPG, PNG 格式</div>
                                        </label>
                                        <div id="image_preview" class="image-preview-container"></div>
                                    </div>
                                </div>
                                <div class="col-12" id="spot_options_container">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">營位選項</h5>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSpotOption()">
                                            <i class="bi bi-plus-lg"></i> 新增營位
                                        </button>
                                    </div>
                                    <div id="spot_options"></div>
                                </div>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '確認新增',
                    cancelButtonText: '取消',
                    didOpen: () => {
                        // 在彈出視窗開啟後設置日期輸入框
                        setupDateInputs();
                        // 設置圖片預覽
                        document.getElementById('main_image')?.addEventListener('change', handleImagePreview);
                    },
                    preConfirm: async () => {
                        try {
                            return await validateAndSubmitForm();
                        } catch (error) {
                            Swal.showValidationMessage(error.message);
                            return false;
                        }
                    }
                });

                if (result.isConfirmed) {
                    await Swal.fire({
                        icon: 'success',
                        title: '新增成功',
                        text: '活動已成功新增',
                        timer: 1500
                    });
                    loadActivities();
                }
            } catch (error) {
                console.error('新增活動失敗:', error);
                Swal.fire({
                    icon: 'error',
                    title: '新增失敗',
                    text: error.message || '無法新增活動'
                });
            }
        }

        // 圖片預覽處理函數
        function handleImagePreview(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('image_preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="image-preview-container">
                            <img src="${e.target.result}" alt="預覽圖片">
                            <button type="button" class="image-preview-remove" onclick="clearImagePreview()">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                clearImagePreview();
            }
        }

        // 清除圖片預覽
        function clearImagePreview() {
            const preview = document.getElementById('image_preview');
            const fileInput = document.getElementById('main_image');
            preview.innerHTML = '';
            fileInput.value = '';
        }

        // 表單驗證和提交
        async function validateAndSubmitForm() {
            try {
                const formData = new FormData();

                // 1. 獲取並驗證基本欄位
                const basicFields = {
                    'application_id': '營地',
                    'activity_name': '活動名稱',
                    'title': '活動標題',
                    'start_date': '開始日期',
                    'end_date': '結束日期'
                };

                for (const [field, label] of Object.entries(basicFields)) {
                    const value = document.getElementById(field)?.value?.trim();
                    if (!value) {
                        throw new Error(`請填寫${label}`);
                    }
                    formData.append(field, value);
                }

                // 2. 驗證日期
                const startDate = new Date(document.getElementById('start_date').value);
                const endDate = new Date(document.getElementById('end_date').value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (startDate <= today) {
                    throw new Error('開始日期必須大於今天');
                }
                if (endDate < startDate) {
                    throw new Error('結束日期必須大於等於開始日期');
                }

                // 3. 處理選填欄位
                const optionalFields = ['subtitle', 'description', 'notice'];
                optionalFields.forEach(field => {
                    const value = document.getElementById(field)?.value?.trim() || '';
                    formData.append(field, value);
                });

                // 4. 處理營位選項
                const spotOptions = [];
                const spotOptionElements = document.querySelectorAll('.spot-option');

                if (spotOptionElements.length === 0) {
                    throw new Error('請至少新增一個營位選項');
                }

                spotOptionElements.forEach((option, index) => {
                    const spotId = option.querySelector('select[name^="spot_options"]')?.value;
                    const price = option.querySelector('input[name$="[price]"]')?.value;
                    const maxQuantity = option.querySelector('input[name$="[max_quantity]"]')?.value;

                    if (!spotId || !price || !maxQuantity) {
                        throw new Error(`請完整填寫第 ${index + 1} 個營位選項的資訊`);
                    }

                    spotOptions.push({
                        spot_id: spotId,
                        price: parseInt(price),
                        max_quantity: parseInt(maxQuantity)
                    });
                });

                // 5. 處理圖片
                const imageFile = document.getElementById('main_image')?.files[0];
                if (!imageFile) {
                    throw new Error('請上傳活動圖片');
                }
                formData.append('images', imageFile);

                // 6. 添加營位選項到 formData
                formData.append('spot_options', JSON.stringify(spotOptions));

                // 7. 發送請求
                console.log('Submitting form data:', Object.fromEntries(formData));
                const response = await axios.post('/CampExplorer/owner/api/activity/create_activity.php', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                console.log('Server response:', response.data);

                if (!response.data.success) {
                    throw new Error(response.data.message || '新增失敗');
                }

                return response.data;
            } catch (error) {
                console.error('表單提交錯誤:', error);
                // 如果是 Axios 錯誤，嘗試獲取詳細的錯誤信息
                if (error.response) {
                    throw new Error(error.response.data.message || error.message);
                }
                throw error;
            }
        }

        // 輔助函數
        function getFieldName(field) {
            const fieldNames = {
                'application_id': '營地',
                'activity_name': '活動名稱',
                'title': '標題',
                'start_date': '開始日期',
                'end_date': '結束日期'
            };
            return fieldNames[field] || field;
        }

        function formatDate(date) {
            if (typeof date === 'string') {
                date = new Date(date);
            }
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // 編輯表單中的移除營位選項函數
        function removeSpotOptionFromEdit(button) {
            const container = document.getElementById('spotOptionsContainer');
            if (!container) return;

            const options = container.querySelectorAll('.spot-option');
            const currentCount = options.length;
            
            // 移除選項前檢查
            if (currentCount <= 1) {
                Swal.fire({
                    icon: 'warning',
                    title: '無法刪除',
                    text: '至少需要保留一個營位選項'
                });
                return;
            }
            
            // 執行移除
            const option = button.closest('.spot-option');
            if (option) {
                option.remove();
            }
        }

        // 編輯表單中的添加營位選項函數
        function addSpotOptionToEdit() {
            const container = document.getElementById('spotOptionsContainer');
            if (!container) return;

            // 獲取目前已選擇的營位
            const selectedSpots = Array.from(container.querySelectorAll('.spot-select'))
                .map(select => select.value)
                .filter(value => value);

            // 過濾出未被選擇的營位
            const availableSpots = window.availableSpots.filter(spot => 
                !selectedSpots.includes(spot.spot_id.toString())
            );

            if (availableSpots.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '無法新增',
                    text: '已無可用的營位選項'
                });
                return;
            }

            const newOption = document.createElement('div');
            newOption.className = 'spot-option mb-2';
            newOption.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-4">
                        <select class="form-select spot-select" required>
                            <option value="">選擇營位</option>
                            ${availableSpots.map(spot => `
                                <option value="${spot.spot_id}">${spot.spot_name}</option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control spot-price" 
                               placeholder="價格" min="0" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control spot-quantity" 
                               placeholder="數量" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="removeSpotOptionFromEdit(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            container.appendChild(newOption);
        }
    </script>
</body>

</html>