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
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--camp-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
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

        /* 價 */
        .price-tag {
            background-color: var(--camp-light);
            color: var(--camp-primary);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: bold;
            display: inline-block;
            border: 2px solid var(--camp-primary);
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

        /* 已通過 - 較深的藍綠色 */
        .status-badge.approved {
            background-color: #4C6B74;  /* 改為較深的藍綠色 */
        }

        /* 不通過 - 莫蘭迪粉 */
        .status-badge.rejected {
            background-color: #D4B5B5;
        }

        /* 使用中 - 較淺的莫蘭迪綠 */
        .status-badge.active {
            background-color: #A8C2B3;  /* 改為較淺的莫蘭迪綠 */
        }

        /* 停用中 - 莫蘭迪粉 */
        .status-badge.inactive {
            background-color: #B47B84;
        }

        /* Hover 效果 */
        .status-badge.pending:hover { background-color: #C4B599; }
        .status-badge.approved:hover { background-color: #3C5B64;  /* hover 時更深 */ }
        .status-badge.rejected:hover { background-color: #C4A5A5; }
        .status-badge.active:hover { background-color: #98B2A3;  /* hover 時更深 */ }
        .status-badge.inactive:hover { background-color: #A46B74; }

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

        /* 統計卡片基本樣式 */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 0.5rem;
            border: 1px solid #E8E8E8;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        /* 狀態卡片顏色 */
        .stat-card.total { border-left: 4px solid var(--camp-primary); }
        .stat-card.pending { border-left: 4px solid #D4C5A9; }
        .stat-card.approved { border-left: 4px solid #4C6B74; }
        .stat-card.rejected { border-left: 4px solid #D4B5B5; }
        .stat-card.active { border-left: 4px solid #A8C2B3; }
        .stat-card.inactive { border-left: 4px solid #B47B84; }

        /* 圖標樣式 */
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        /* 圖標背景顏色 */
        .stat-card.total .stat-icon { background: #EDF2F7; color: var(--camp-primary); }
        .stat-card.pending .stat-icon { background: #F7F4ED; color: #D4C5A9; }
        .stat-card.approved .stat-icon { background: #EDF5F1; color: #4C6B74; }
        .stat-card.rejected .stat-icon { background: #F7EDED; color: #D4B5B5; }
        .stat-card.active .stat-icon { background: #E6F5F2; color: #A8C2B3; }
        .stat-card.inactive .stat-icon { background: #F5E6E9; color: #B47B84; }

        /* Hover 效果 */
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .stat-card.total:hover { background: var(--camp-primary); }
        .stat-card.pending:hover { background: #D4C5A9; }
        .stat-card.approved:hover { background: #4C6B74; }
        .stat-card.rejected:hover { background: #D4B5B5; }
        .stat-card.active:hover { background: #A8C2B3; }
        .stat-card.inactive:hover { background: #B47B84; }

        /* Hover 時的文字和圖標顏色 */
        .stat-card:hover .stat-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .stat-card:hover .stat-number,
        .stat-card:hover .stat-label {
            color: white;
        }

        .stat-content {
            flex: 1;
        }

        .stat-number {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #94A3B8;
            margin: 0;
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

        /* 統計卡片狀態樣式 */
        .stat-card.active {
            border-left: 4px solid #A8C2B3;
        }

        .stat-card.active .stat-icon {
            background-color: #EDF5F1;
            color: #A8C2B3;
        }

        .stat-card.active:hover {
            background: linear-gradient(135deg, #A8C2B3 0%, #C4D8CD 100%);
        }

        .stat-card.available {
            border-left: 4px solid #D4C5A9;
        }

        .stat-card.available .stat-icon {
            background-color: #F7F4ED;
            color: #D4C5A9;
        }

        .stat-card.available:hover {
            background: linear-gradient(135deg, #D4C5A9 0%, #E5DBC8 100%);
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

        /* 已通過 - 較深的藍綠色 */
        .stat-card.approved {
            border-left: 4px solid #4C6B74;  /* 改為較深的藍綠色 */
        }
        .stat-card.approved .stat-icon {
            background-color: #EDF5F1;
            color: #4C6B74;
        }
        .stat-card.approved:hover {
            background: linear-gradient(135deg, #4C6B74 0%, #C4D8CD 100%);
        }

        /* 使用中 - 較淺的莫蘭迪綠 */
        .stat-card.active {
            border-left: 4px solid #A8C2B3;
        }
        .stat-card.active .stat-icon {
            background-color: #EDF2F7;
            color: #A8C2B3;
        }
        .stat-card.active:hover {
            background: linear-gradient(135deg, #A8C2B3 0%, #C4D8CD 100%);
        }

        /* 停用中 - 莫蘭迪粉 */
        .stat-card.inactive {
            border-left: 4px solid #D4B5B5;
        }
        .stat-card.inactive .stat-icon {
            background-color: #F7EDED;
            color: #D4B5B5;
        }
        .stat-card.inactive:hover {
            background: linear-gradient(135deg, #D4B5B5 0%, #E5CDCD 100%);
        }

        .status-container {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.8rem;
            border-radius: 16px;
            font-size: 0.875rem;
            white-space: nowrap;
            color: white;
        }

        .status-badge i {
            margin-right: 0.3rem;
        }

        /* 審核狀態顏色 */
        .status-badge.review.pending { background-color: var(--camp-warning); }
        .status-badge.review.approved { background-color: var(--camp-primary); }
        .status-badge.review.rejected { background-color: var(--camp-danger); }

        /* 營運狀態顏色 */
        .status-badge.operation.active { background-color: #8FB3A9; }
        .status-badge.operation.inactive { background-color: #B47B84; }

        /* 未知狀態 */
        .status-badge.unknown { background-color: var(--camp-secondary); }

        /* 操作按鈕容器 */
        .d-flex.gap-2 {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        /* 按鈕基本樣式 */
        .btn-action {
            padding: 0.4rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background-color: white;
            min-width: 72px;  /* 設定最小寬度 */
            text-align: center;
            height: 32px;     /* 統一高度 */
            line-height: 1;   /* 調整文字垂直置中 */
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* 停用狀態的按鈕樣式 */
        .btn-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        /* 編輯按鈕 */
        .btn-edit {
            border: 1px solid var(--camp-primary);
            color: var(--camp-primary);
        }

        .btn-edit:hover:not(:disabled) {
            background-color: var(--camp-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
        }

        /* 啟用按鈕 */
        .btn-activate {
            border: 1px solid #A8C2B3;
            color: #A8C2B3;
        }

        .btn-activate:hover:not(:disabled) {
            background-color: #A8C2B3;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(168, 194, 179, 0.2);
        }

        /* 停用按鈕 */
        .btn-deactivate {
            border: 1px solid #B47B84;
            color: #B47B84;
        }

        .btn-deactivate:hover:not(:disabled) {
            background-color: #B47B84;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(180, 123, 132, 0.2);
        }

        /* 統計卡片標籤文字顏色對應調整 */
        .stat-card.approved .stat-label { color: #4C6B74; }  /* 深藍綠色 */
        .stat-card.active .stat-label { color: #A8C2B3; }    /* 淺莫蘭迪綠 */

        /* Hover 時文字變白 */
        .stat-card:hover .stat-label {
            color: white;
        }

        /* 次要按鈕（取消）- 空心 */
        .btn-secondary {
            background-color: transparent;
            border: 1px solid var(--camp-primary);
            color: var(--camp-primary);
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: var(--camp-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
        }

        /* 主要按鈕（確認修改）- 實心 */
        .btn-confirm {
            background-color: var(--camp-primary) !important;
            color: white !important;
            padding: 0.5rem 1.5rem !important;
            border-radius: 12px !important;
            font-size: 0.875rem !important;
            transition: all 0.3s ease !important;
            border: none !important;
        }

        .btn-confirm:hover {
            background-color: var(--camp-primary-dark) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2) !important;
        }

        .btn-cancel {
            background-color: transparent !important;
            border: 1px solid var(--camp-danger) !important;
            color: var(--camp-danger) !important;
            padding: 0.5rem 1.5rem !important;
            border-radius: 12px !important;
            font-size: 0.875rem !important;
            transition: all 0.3s ease !important;
        }

        .btn-cancel:hover {
            background-color: var(--camp-danger) !important;
            color: white !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(180, 123, 132, 0.2) !important;
        }

        .btn-confirm:active,
        .btn-cancel:active {
            transform: translateY(0) !important;
            box-shadow: 0 2px 6px rgba(76, 107, 116, 0.15) !important;
        }

        /* SweetAlert2 按鈕樣式 */
        .swal2-actions {
            gap: 1rem !important;
        }

        .swal2-wide-button {
            min-width: 200px !important;  /* 設定最小寬度 */
            padding: 0.5rem 2rem !important;
        }

        .btn-confirm {
            background-color: var(--camp-primary) !important;
            color: white !important;
            border-radius: 12px !important;
            font-size: 0.875rem !important;
            transition: all 0.3s ease !important;
            border: none !important;
        }

        .btn-cancel {
            background-color: transparent !important;
            border: 1px solid var(--camp-danger) !important;
            color: var(--camp-danger) !important;
            border-radius: 12px !important;
            font-size: 0.875rem !important;
            transition: all 0.3s ease !important;
        }

        /* SweetAlert2 寬按鈕樣式 */
        .swal2-wide-confirm {
            min-width: 180px !important;
            padding: 0.75rem 2rem !important;
            font-size: 1rem !important;
            font-weight: 500 !important;
            border-radius: 12px !important;
            background-color: var(--camp-primary) !important;
            color: white !important;
            transition: all 0.3s ease !important;
        }

        .swal2-wide-confirm:hover {
            background-color: var(--camp-primary-dark) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2) !important;
        }

        .swal2-wide-confirm:active {
            transform: translateY(0) !important;
            box-shadow: 0 2px 6px rgba(76, 107, 116, 0.15) !important;
        }

        .stat-card {
            cursor: pointer;
        }
        
        .stat-card.selected {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card.selected .stat-icon,
        .stat-card.selected .stat-number,
        .stat-card.selected .stat-label {
            color: white;
        }
        
        .stat-card.selected.total { background-color: var(--camp-primary); }
        .stat-card.selected.pending { background-color: #D4C5A9; }
        .stat-card.selected.approved { background-color: #4C6B74; }
        .stat-card.selected.rejected { background-color: #D4B5B5; }
        .stat-card.selected.active { background-color: #A8C2B3; }
        .stat-card.selected.inactive { background-color: #B47B84; }
        
        .stat-card.selected .stat-icon {
            background-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="page-container">
        <!-- 統計卡片區域 -->
        <div class="stats-container">
            <div class="stat-card total">
                <div class="stat-icon"><i class="bi bi-grid-fill"></i></div>
                <div class="stat-content">
                    <div class="stat-number" id="totalSpots">0</div>
                    <div class="stat-label">總營位數</div>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-content">
                    <div class="stat-number" id="pendingSpots">0</div>
                    <div class="stat-label">審核中</div>
                </div>
            </div>
            
            <div class="stat-card approved">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-number" id="approvedSpots">0</div>
                    <div class="stat-label">已通過</div>
                </div>
            </div>
            
            <div class="stat-card rejected">
                <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-number" id="rejectedSpots">0</div>
                    <div class="stat-label">未通過</div>
                </div>
            </div>
            
            <div class="stat-card active">
                <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-number" id="activeSpots">0</div>
                    <div class="stat-label">使用中</div>
                </div>
            </div>
            
            <div class="stat-card inactive">
                <div class="stat-icon"><i class="bi bi-stop-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-number" id="inactiveSpots">0</div>
                    <div class="stat-label">停用中</div>
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
        // 添加快取變數和載入狀態制
        let spotsCache = null;
        let lastFetchTime = null;
        const CACHE_DURATION = 30000; // 30秒快取
        let isLoading = false;

        // 優化後的 getSpots 函數
        async function getSpots(forceRefresh = false) {
            try {
                const response = await axios.get('/CampExplorer/owner/api/spots/get_owner_spots.php');
                console.log('API 回應:', response.data);
                
                if (response.data.success) {
                    const spots = response.data.spots;
                    spots.forEach(spot => {
                        console.log('位資料:', {
                            id: spot.spot_id,
                            name: spot.spot_name,
                            application_status: spot.application_status,
                            status: spot.status
                        });
                    });
                    updateUI(spots);
                    updateStats(spots);
                } else {
                    throw new Error(response.data.message || '載入失敗');
                }
            } catch (error) {
                console.error('載入營位失敗:', error);
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
            const spotList = document.querySelector('.spot-list tbody');
            spotList.innerHTML = spots.map(spot => `
                <tr>
                    <td>
                        <img src="${spot.image_path || '/CampExplorer/images/default-spot.jpg'}" 
                             alt="${spot.spot_name}" 
                             class="spot-image"
                             onerror="this.src='/CampExplorer/images/default-spot.jpg'">
                    </td>
                    <td>
                        <div class="fw-bold">${spot.spot_name}</div>
                        <small class="text-muted">${spot.camp_name}</small>
                    </td>
                    <td>${spot.capacity} 人</td>
                    <td>NT$ ${spot.price}</td>
                    <td>${getStatusText(spot)}</td>
                    <td>${getActionButtons(spot)}</td>
                </tr>
            `).join('');
        }

        // 分離統計更新邏輯
        function updateStatistics(spots) {
            console.log('Updating stats with spots:', spots); // 調試用
            
            const stats = {
                total: spots.length,
                pending: spots.filter(spot => 
                    spot.application_status === '0' || spot.application_status === 0
                ).length,
                approved: spots.filter(spot => 
                    spot.application_status === '1' || spot.application_status === 1
                ).length,
                active: spots.filter(spot => 
                    (spot.application_status === '1' || spot.application_status === 1) &&
                    (spot.status === '1' || spot.status === 1)
                ).length,
                inactive: spots.filter(spot => 
                    (spot.application_status === '1' || spot.application_status === 1) &&
                    (spot.status === '0' || spot.status === 0)
                ).length
            };

            // 更新統計數字
            document.getElementById('totalSpots').textContent = stats.total;
            document.getElementById('pendingSpots').textContent = stats.pending;
            document.getElementById('approvedSpots').textContent = stats.approved;
            document.getElementById('activeSpots').textContent = stats.active;
            document.getElementById('inactiveSpots').textContent = stats.inactive;

            animateStatNumbers();
        }

        // 分離列表更新邏輯
        function updateSpotsList(spots) {
            console.log('Updating spots list with:', spots); // 調試用
            
            const spotList = document.querySelector('#spot-list tbody');
            if (!spotList) return;
            
            spotList.innerHTML = spots.map(spot => `
                <tr>
                    <td>${spot.spot_name}</td>
                    <td>${spot.description}</td>
                    <td>
                        <span class="status-badge ${getStatusClass(spot)}" 
                              data-review-status="${spot.review_status}"
                              data-is-active="${spot.is_active}">
                            ${getStatusText(spot)}
                        </span>
                    </td>
                    <!-- 其他欄位 -->
                </tr>
            `).join('');
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
                initializeStatCards();
            } else {
                stopAutoRefresh();
            }
        }

        // 添加統計卡片點擊事件
        function initializeStatCards() {
            const statCards = document.querySelectorAll('.stat-card');
            let currentFilter = 'total';
            
            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    // 移除所有卡片的選中狀態
                    statCards.forEach(c => c.classList.remove('selected'));
                    
                    // 獲取過濾類型
                    const filterType = Array.from(this.classList)
                        .find(cls => ['total', 'pending', 'approved', 'rejected', 'active', 'inactive']
                        .includes(cls)) || 'total';
                    
                    // 如果點擊當前選中的卡片，重置為總覽
                    if (currentFilter === filterType) {
                        currentFilter = 'total';
                        filterSpots('total');
                    } else {
                        // 選擇新的篩選條件
                        currentFilter = filterType;
                        this.classList.add('selected');
                        filterSpots(filterType);
                    }
                });
            });
        }

        // 修改 filterSpots 函數中的狀態判斷邏輯
        function filterSpots(filterType) {
            const rows = document.querySelectorAll('#spot-list tbody tr');
            
            rows.forEach(row => {
                const statusBadges = row.querySelectorAll('.status-badge');
                const statusTexts = Array.from(statusBadges).map(badge => badge.textContent.trim());
                let show = false;

                switch(filterType) {
                    case 'total':
                        show = true;
                        break;
                    case 'pending':
                        show = statusTexts.includes('審核中');
                        break;
                    case 'approved':
                        show = statusTexts.includes('已通過') && !statusTexts.includes('不通過');
                        break;
                    case 'rejected':
                        show = statusTexts.includes('不通過');
                        break;
                    case 'active':
                        show = statusTexts.includes('已通過') && statusTexts.includes('使用中');
                        break;
                    case 'inactive':
                        show = statusTexts.includes('已通過') && statusTexts.includes('停用中');
                        break;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }

        // 編輯營位
        async function editSpot(spotId) {
            try {
                // 獲取營位資料
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
                    throw new Error('無法取營位資料');
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
                    customClass: {
                        confirmButton: 'btn-confirm swal2-wide-button',
                        cancelButton: 'btn-cancel swal2-wide-button',
                        actions: 'swal2-actions'
                    },
                    buttonsStyling: false,
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
                            customClass: {
                                confirmButton: 'btn-confirm'
                            },
                            buttonsStyling: false,
                            confirmButtonText: '確定',
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
                    customClass: {
                        confirmButton: 'btn-confirm',
                        cancelButton: 'btn-cancel'
                    },
                    buttonsStyling: false,
                    confirmButtonText: isActive ? '確定啟用' : '確定停用',
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
                        customClass: {
                            confirmButton: 'btn-confirm'
                        },
                        buttonsStyling: false,
                        confirmButtonText: '確定',
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

        // 數字動畫效果函數
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

        // 統計數字動畫效果
        function animateStatNumbers() {
            const elements = document.querySelectorAll('.stat-number');
            elements.forEach(element => {
                const endValue = parseInt(element.textContent);
                animateValue(element, 0, endValue, 1000);
            });
        }

        // 獲取狀態對應的 CSS class
        function getStatusClass(spot) {
            console.log('Checking status for spot:', spot); // 添加調試日誌
            
            // 審核狀態
            const appStatus = spot.application_status;
            const opStatus = spot.status;
            
            console.log('Application status:', appStatus);
            console.log('Operation status:', opStatus);
            
            if (appStatus === '0' || appStatus === 0) return 'pending';
            if (appStatus === '2' || appStatus === 2) return 'rejected';
            if (appStatus === '1' || appStatus === 1) {
                return opStatus === '1' || opStatus === 1 ? 'active' : 'inactive';
            }
            return '';
        }

        // 確保狀態標籤生成正確
        function getStatusText(spot) {
            let statusHtml = '<div class="status-container">';
            
            // 審核狀態
            const appStatus = Number(spot.application_status);
            const opStatus = Number(spot.status);
            
            // 先添加審核狀態
            switch(appStatus) {
                case 0:
                    statusHtml += '<span class="status-badge pending">審核中</span>';
                    break;
                case 1:
                    statusHtml += '<span class="status-badge approved">已通過</span>';
                    // 如果審核通過，明確添加營運狀態標籤
                    if (opStatus === 1) {
                        statusHtml += '<span class="status-badge active">使用中</span>';
                    } else {
                        statusHtml += '<span class="status-badge inactive">停用中</span>';
                    }
                    break;
                case 2:
                    statusHtml += '<span class="status-badge rejected">不通過</span>';
                    break;
                default:
                    statusHtml += '<span class="status-badge unknown">未知狀態</span>';
            }
            
            statusHtml += '</div>';
            return statusHtml;
        }

        // 更新統計數據
        function updateStats(spots) {
            const stats = {
                total: spots.length,
                pending: spots.filter(spot => 
                    spot.application_status === '0' || spot.application_status === 0
                ).length,
                approved: spots.filter(spot => 
                    spot.application_status === '1' || spot.application_status === 1
                ).length,
                rejected: spots.filter(spot => 
                    spot.application_status === '2' || spot.application_status === 2
                ).length,
                active: spots.filter(spot => 
                    (spot.application_status === '1' || spot.application_status === 1) &&
                    (spot.status === '1' || spot.status === 1)
                ).length,
                inactive: spots.filter(spot => 
                    (spot.application_status === '1' || spot.application_status === 1) &&
                    (spot.status === '0' || spot.status === 0)
                ).length
            };

            // 更新統計數字
            Object.keys(stats).forEach(key => {
                const element = document.getElementById(`${key}Spots`);
                if (element) element.textContent = stats[key];
            });

            // 添加動畫效果
            animateStatNumbers();
        }

        function getActionButtons(spot) {
            let buttonsHtml = '<div class="d-flex gap-2">';
            
            // 編輯按鈕
            buttonsHtml += `
                <button class="btn btn-sm btn-action btn-edit" 
                        onclick="editSpot(${spot.spot_id})" 
                        title="編輯營位">
                    編輯營位
                </button>`;
            
            // 所有狀態都顯示停用按鈕，但只有審核通過的可以點擊
            const isActive = Number(spot.status) === 1;
            const isApproved = Number(spot.application_status) === 1;
            
            buttonsHtml += `
                <button class="btn btn-sm btn-action ${isActive ? 'btn-deactivate' : 'btn-activate'}"
                        onclick="toggleSpotStatus(${spot.spot_id}, ${!isActive})"
                        ${!isApproved ? 'disabled' : ''}
                        title="${isActive ? '停用營位' : '啟用營位'}">
                    ${isActive ? '停用' : '啟用'}
                </button>`;
            
            buttonsHtml += '</div>';
            return buttonsHtml;
        }
    </script>
</body>

</html>