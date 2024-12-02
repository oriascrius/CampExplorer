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

$owner_id = $_SESSION['owner_id'];
$stats = [
    'total' => 0,
    'cancelled' => 0,
    'confirmed' => 0,
    'pending' => 0
];

try {
    // 查詢所有訂單並計算統計數據
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM bookings b
        JOIN activity_spot_options aso ON b.option_id = aso.option_id
        JOIN spot_activities sa ON aso.activity_id = sa.activity_id
        WHERE sa.owner_id = ?
    ");

    $stmt->execute([$owner_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $stats = [
            'total' => $result['total'] ?? 0,
            'cancelled' => $result['cancelled'] ?? 0,
            'confirmed' => $result['confirmed'] ?? 0,
            'pending' => $result['pending'] ?? 0
        ];
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        :root {
            --camp-primary: #4C6B74;
            /* 主色調：深灰藍 */
            --camp-primary-dark: #3A545C;
            /* 深色主調 */
            --camp-secondary: #94A7AE;
            /* 次要色：淺灰藍 */
            --camp-light: #F5F7F8;
            /* 景色：淺灰 */
            --camp-border: #E3E8EA;
            /* 邊框色 */
            --camp-text: #2A4146;
            /* 文字色 */
            --camp-warning: #B4A197;
            /* 警告色：莫蘭迪棕 */
            --camp-warning-dark: #9B8A81;
            /* 深警告色 */
            --camp-danger: #B47B84;
            /* 危險色：莫蘭迪粉 */
            --status-pending: #C4A687;
            /* 待確認：莫蘭迪沙 */
            --status-confirmed: #8FA977;
            /* 已確認：鼠尾草綠 */
            --status-cancelled: #C69B97;
            /* 已取消：莫蘭迪玫瑰 */
            --morandi-danger: #e57373;
            --morandi-primary: #7A90A8;
            /* 主色調：莫蘭迪藍 */
            --morandi-secondary: #A68E9B;
            /* 次要色：莫蘭迪紫灰 */
            --morandi-success: #8FA977;
            /* 成功狀態：鼠尾草綠 */
            --morandi-warning: #C4A687;
            /* 警告狀態：莫蘭迪沙 */
            --morandi-info: #89B0A3;
            /* 信息狀態：莫蘭迪薄荷 */
            --morandi-light: #F3F1ED;
            /* 背景色：米白色 */
            --morandi-border: #D8D0C5;
            /* 邊框色：淺棕色 */
        }

        .page-title {
            color: var(--camp-primary);
            font-size: 2rem;
            font-weight: 600;
            /* margin-bottom: 1rem;
            padding-bottom: 1rem; */
            /* border-bottom: 3px solid var(--camp-border); */
            position: relative;
        }

        /* 統計卡片基本樣式 */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 0.3rem 0.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid var(--camp-border);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        /* 不同片邊框 */
        .stat-card.all {
            border-left: 4px solid var(--camp-primary);
        }

        .stat-card.pending {
            border-left: 4px solid var(--camp-warning);
        }

        .stat-card.confirmed {
            border-left: 4px solid var(--camp-secondary);
        }

        .stat-card.cancelled {
            border-left: 4px solid var(--camp-danger);
        }

        /* 圖標容器樣式 */
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* 不同狀態的圖標色和圖標 */
        .stat-card.all .stat-icon {
            background-color: rgba(76, 107, 116, 0.1);
            color: var(--camp-primary);
        }

        .stat-card.pending .stat-icon {
            background-color: rgba(196, 166, 135, 0.1);
            color: var(--status-pending);
        }

        .stat-card.confirmed .stat-icon {
            background-color: rgba(143, 169, 119, 0.1);
            color: var(--status-confirmed);
        }

        .stat-card.cancelled .stat-icon {
            background-color: rgba(198, 155, 151, 0.1);
            color: var(--status-cancelled);
        }

        /* Hover 效果時圖背景變為半透明白色 */
        .stat-card:hover .stat-icon {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* 文字內容樣式 */
        .stat-content {
            flex-grow: 1;
        }

        .stat-number {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--camp-text);
            margin-bottom: 0.25rem;
        }

        /* 分類名稱顏色 */
        .stat-card.all .stat-label {
            color: var(--camp-primary);
        }

        .stat-card.pending .stat-label {
            color: var(--camp-warning);
        }

        .stat-card.confirmed .stat-label {
            color: var(--camp-secondary);
        }

        .stat-card.cancelled .stat-label {
            color: var(--camp-danger);
        }

        /* Hover 效果 */
        .stat-card.all:hover {
            background-color: var(--camp-primary);
        }

        .stat-card.pending:hover {
            background-color: var(--camp-warning);
        }

        .stat-card.confirmed:hover {
            background-color: var(--camp-secondary);
        }

        .stat-card.cancelled:hover {
            background-color: var(--camp-danger);
        }

        .stat-card:hover .stat-number,
        .stat-card:hover .stat-label,
        .stat-card:hover .stat-icon {
            color: white;
        }

        /* 主要內容區域樣式 */
        .content-wrapper {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .stats-container {
            display: grid;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        /* 表格基本樣式 */
        .table {
            color: #495057;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        /* 表頭樣式 */
        .table thead th {
            background: var(--camp-primary);
            color: white;
            font-weight: 500;
            padding: 1rem;
            border: none;
            white-space: nowrap;
        }

        .table thead th:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .table thead th:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        /* 表格內容樣 */
        .table td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
            border-color: var(--camp-border);
        }

        /* 表格行懸停效果 */
        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: var(--camp-light);
        }

        /* 狀態標籤基本樣式 */
        .status-badge {
            padding: 0.5em 1em;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        /* 狀態標籤樣 */
        .status-badge {
            padding: 0.5em 1em;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: var(--status-pending);
            color: white;
        }

        .status-confirmed {
            background-color: var(--status-confirmed);
            color: white;
        }

        .status-cancelled {
            background-color: var(--status-cancelled);
            color: white;
        }

        /* 狀態標籤停效果 */
        .status-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* 響應式調整 */
        @media (max-width: 768px) {
            .status-badge {
                font-size: 0.8rem;
            }
        }

        /* 按鈕組樣式 */
        .btn-group-sm>.btn {
            padding: 0.25rem 0.5rem;
        }

        .btn-group-sm>.btn i {
            font-size: 0.875rem;
        }

        .btn-success {
            background-color: var(--status-confirmed);
            border-color: var(--status-confirmed);
        }

        .btn-danger {
            background-color: var(--status-cancelled);
            border-color: var(--status-cancelled);
        }

        .btn-secondary:disabled {
            background-color: var(--camp-secondary);
            border-color: var(--camp-secondary);
            opacity: 0.7;
        }

        .btn-outline-primary {
            color: var(--morandi-primary);
            border-color: var(--morandi-primary);
            background-color: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            color: white;
            background-color: var(--morandi-primary);
            border-color: var(--morandi-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-outline-primary:active,
        .btn-outline-primary:focus {
            color: white;
            background-color: var(--morandi-primary);
            border-color: var(--morandi-primary);
            box-shadow: 0 0 0 0.2rem rgba(122, 144, 168, 0.25);
        }

        /* 按鈕基本樣式 */
        .btn-action {
            padding: 0.5rem 1.2rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            min-width: 100px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* 編輯按鈕樣式 */
        .btn-edit {
            color: var(--camp-primary);
            background-color: transparent;
            border: 1px solid var(--camp-primary);
        }

        .btn-edit:hover {
            background-color: var(--camp-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Modal 樣式優化 */
        .modal-content {
            border: none;
            border-radius: 15px;
            transform: scale(0.95);
            opacity: 0;
            transition: all 0.3s ease;
            width: 800px;
        }

        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-contentEdit {
            width: 500px;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--camp-primary) 0%, var(--camp-secondary) 100%);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            justify-content: space-around;
            padding: 1.5rem 2rem;
        }

        .modal-footer .btn {
            min-width: 120px;
            padding: 0.6rem 1.2rem;
        }

        .btn-outline-cancel {
            color: var(--camp-danger);
            background-color: transparent;
            border: 1.5px solid var(--camp-danger);
        }

        .btn-outline-cancel:hover {
            color: white;
            background-color: var(--camp-danger);
        }

        /* 取消按鈕樣式 */
        .btn-cancel {
            color: var(--camp-text);
            background-color: transparent;
            border: 1px solid var(--camp-text);
        }

        .btn-cancel:hover {
            background-color: var(--camp-text);
            color: white;
        }

        /* 確認按鈕樣式 */
        .btn-primary {
            background-color: var(--morandi-info);
            border: 1.5px solid var(--morandi-info);
            color: white;
            transition: all 0.3s ease;
            padding: 0.6rem 2rem;
            min-width: 120px;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: transparent;
            border-color: var(--morandi-info);
            color: var(--morandi-info);
            box-shadow: none;
            transform: translateY(-2px);
        }

        .btn-primary:active {
            background-color: transparent !important;
            border-color: var(--morandi-info) !important;
            color: var(--morandi-info) !important;
            box-shadow: none !important;
        }

        /* 按組式 */
        .d-flex.gap-2 {
            display: flex;
            gap: 0.5rem;
        }

        /* 按鈕動畫效果 */
        .btn-action {
            position: relative;
            overflow: hidden;
        }

        .btn-action::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }

        .btn-action:active::after {
            width: 200%;
            height: 200%;
        }

        /* 詳情片樣式 */
        .info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .info-card-header {
            background: var(--morandi-light);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--morandi-border);
        }

        .info-card-header i {
            color: var(--morandi-primary);
            font-size: 1.2rem;
        }

        .info-card-header h6 {
            margin: 0;
            color: var(--morandi-text);
            font-weight: 600;
        }

        .info-list {
            padding: 1.5rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px dashed var(--morandi-border);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--morandi-secondary);
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--morandi-text);
        }

        .total-price {
            margin-top: 0.5rem;
            padding-top: 1rem;
            border-top: 2px solid var(--morandi-border);
        }

        .total-price .info-value {
            font-size: 1.2rem;
            color: var(--morandi-primary);
        }

        /* Modal 動畫效果 */
        .modal.fade .modal-dialog {
            transform: scale(0.95);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.show .modal-dialog {
            transform: scale(1);
            opacity: 1;
        }

        /* 狀態選擇按鈕組樣式 */
        .status-options {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            padding: 0.5rem;
        }

        .status-option {
            position: relative;
        }

        .status-radio {
            display: none;
        }

        .status-label {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        /* 狀態標籤顏色 */
        .status-label.pending {
            background-color: #D4C5A9;
            /* 莫蘭迪黃 */
        }

        .status-label.confirmed {
            background-color: #A8C2B3;
            /* 莫蘭迪綠 */
        }

        .status-label.cancelled {
            background-color: #D4B5B5;
            /* 莫蘭迪粉 */
        }

        /* 選效果 */
        .status-radio:checked+.status-label {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Modal 按鈕樣式 */
        .modal-footer .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
        }

        /* 取消按鈕 - 空心 */
        .modal-footer .btn-outline-secondary {
            color: var(--camp-secondary);
            background-color: transparent;
            border: 1px solid var(--camp-secondary);
        }

        .modal-footer .btn-outline-secondary:hover {
            color: white;
            background-color: var(--camp-secondary);
        }

        /* 確認更新按鈕 - 實心 */
        .modal-footer .btn-primary {
            color: white;
            background-color: var(--camp-primary);
            border: none;
        }

        .modal-footer .btn-primary:hover {
            background-color: var(--camp-primary-dark);
            color: white;
        }

        /* 狀態選項樣式 */
        .status-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .status-option {
            position: relative;
            padding: 1rem;
            border: 2px solid var(--camp-border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-option:hover {
            border-color: var(--morandi-primary);
            background-color: rgba(122, 144, 168, 0.05);
        }

        .status-option.current {
            border-color: var(--morandi-primary);
            background-color: rgba(122, 144, 168, 0.1);
        }

        .status-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .status-option .status-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            cursor: pointer;
        }

        .status-option .status-label i {
            font-size: 1.2rem;
        }

        /* 不同狀態的顏色 */
        .status-option[data-status="pending"] .status-label i {
            color: var(--status-pending);
        }

        .status-option[data-status="confirmed"] .status-label i {
            color: var(--status-confirmed);
        }

        .status-option[data-status="cancelled"] .status-label i {
            color: var(--status-cancelled);
        }

        /* 詳細訂單 Modal */
        .detail-modal .modal-dialog {
            max-width: 900px;
        }

        .detail-modal .modal-body {
            padding: 2.5rem;
        }

        .info-section {
            background-color: var(--morandi-light);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .info-title {
            color: var(--morandi-primary);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            padding: 0.8rem 0;
        }

        .info-label {
            color: var(--morandi-secondary);
            font-size: 1rem;
            min-width: 120px;
        }

        .info-value {
            color: #495057;
            font-weight: 500;
            flex: 1;
            text-align: right;
        }

        .total-price {
            border-top: 2px solid var(--morandi-border);
            margin-top: 1.5rem;
            padding-top: 1.5rem;
        }

        .total-price .info-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* 按鈕樣式 */
        .btn-outline-secondary {
            color: var(--morandi-secondary);
            border: 1.5px solid var(--morandi-secondary);
            background-color: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background-color: var(--morandi-secondary);
            color: white;
        }

        /* 關閉按鈕樣式 */
        .btn-outline-danger {
            color: var(--camp-danger);
            border: 1.5px solid var(--camp-danger);
            background-color: transparent;
            padding: 0.6rem 2rem;
            min-width: 120px;
            transition: all 0.3s ease;
        }

        .btn-outline-danger:hover {
            background-color: var(--camp-danger);
            border-color: var(--camp-danger);
            color: white;
        }

        /* 主要按鈕樣式 */
        .btn-primary {
            background-color: var(--morandi-primary);
            border: 1.5px solid var(--morandi-primary);
            color: white;
            transition: all 0.3s ease;
            padding: 0.6rem 2rem;
            min-width: 120px;
        }


        .btn-primary:hover,
        .btn-primary:focus {
            background-color: transparent;
            border-color: transparent;
            color: var(--morandi-primary);
            box-shadow: none;
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            background-color: transparent;
            color: var(--morandi-primary);
        }

        .btn-primary:active {
            background-color: transparent !important;
            border-color: var(--morandi-primary) !important;
            color: var(--morandi-primary) !important;
            box-shadow: none !important;
        }

        /* 編狀態 Modal 的確認按鈕樣式 */
        .modal-contentEdit .btn-primary {
            background-color: var(--morandi-primary);
            border: 1.5px solid var(--morandi-primary);
            color: white;
            transition: all 0.3s ease;
        }

        .modal-contentEdit .btn-primary:hover,
        .modal-contentEdit .btn-primary:focus {
            background-color: transparent;
            border-color: var(--morandi-primary);
            color: var(--morandi-primary);
            box-shadow: none;
            transform: translateY(-2px);
        }

        /* 狀態選項的高亮樣式 */
        .status-option {
            position: relative;
        }

        .status-option.current::after {
            content: '目前狀態';
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.8rem;
            color: var(--morandi-secondary);
        }

        .status-option.current .status-label {
            border-color: var(--morandi-primary);
            background-color: rgba(122, 144, 168, 0.1);
        }

        /* 狀態選項的動畫效果 */
        .status-option {
            transition: all 0.3s ease;
        }

        .status-option:hover {
            transform: translateY(-2px);
        }

        /* 狀態標籤的樣式 */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-pending {
            background-color: var(--status-pending);
            color: white;
        }

        .status-confirmed {
            background-color: var(--status-confirmed);
            color: white;
        }

        .status-cancelled {
            background-color: var(--status-cancelled);
            color: white;
        }

        /* 表格行懸停效果 */
        #bookingsList tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Modal 樣式 */
        .modal-lg {
            max-width: 800px;
        }

        /* 表格樣式 */
        .table-borderless td {
            padding: 0.5rem;
        }

        /* 詳細資料區樣式 */
        .modal-body h6 {
            color: #666;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        /* 狀態標籤統一樣式 */
        .badge {
            padding: 0.5em 0.8em;
            font-size: 0.9em;
            color: white;
            border-radius: 12px;
        }

        /* Modal 按鈕樣式 */
        .btn-outline-secondary {
            color: var(--camp-gray);
            border-color: var(--camp-gray);
        }

        .btn-outline-secondary:hover {
            color: white;
            background-color: var(--camp-gray);
        }

        .btn-outline-primary {
            color: var(--camp-primary);
            border-color: var(--camp-primary);
        }

        .btn-outline-primary:hover {
            color: white;
            background-color: var(--camp-primary);
        }

        /* 狀態選項樣式 */
        .status-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .status-option {
            position: relative;
        }

        .status-radio {
            display: none;
        }

        .status-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            border-radius: 0.5rem;
            color: white;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .status-label:hover {
            transform: translateX(5px);
        }

        .status-radio:checked+.status-label {
            box-shadow: 0 0 0 2px white, 0 0 0 4px currentColor;
        }

        /* Modal 按鈕樣 */
        .modal-footer .btn {
            padding: 0.5rem 1.5rem;
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        /* 取消按鈕 - 空心 */
        .modal-footer .btn-secondary {
            color: var(--camp-secondary);
            background-color: transparent;
            border: 1px solid var(--camp-secondary);
        }

        .modal-footer .btn-secondary:hover {
            color: white;
            background-color: var(--camp-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(148, 167, 174, 0.2);
        }

        /* 更改狀態按鈕 - 實心 */
        .modal-footer .btn-primary {
            color: white;
            background-color: var(--camp-primary);
            border: none;
        }

        .modal-footer .btn-primary:hover {
            background-color: var(--camp-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 107, 116, 0.2);
        }

        /* 按下效果 */
        .modal-footer .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(76, 107, 116, 0.15);
        }

        /* 狀態標籤樣式 */
        .status-badge {
            padding: 0.5em 0.8em;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
        }

        /* 狀態標籤顏色 */
        .status-badge.pending {
            background-color: #D4C5A9;
            /* 莫蘭迪黃 */
        }

        .status-badge.confirmed {
            background-color: #A8C2B3;
            /* 莫蘭迪綠 */
        }

        .status-badge.cancelled {
            background-color: #D4B5B5;
            /* 莫蘭迪粉 */
        }

        /* 狀態標籤 hover 效果 */
        .status-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .status-badge.pending:hover {
            background-color: #C4B599;
        }

        .status-badge.confirmed:hover {
            background-color: #98B2A3;
        }

        .status-badge.cancelled:hover {
            background-color: #C4A5A5;
        }

        /* Modal 關閉按鈕樣式 */
        .modal-footer .btn-outline-secondary {
            color: var(--camp-secondary);
            background-color: transparent;
            border: 1px solid var(--camp-secondary);
            padding: 0.5rem 1.5rem;
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .modal-footer .btn-outline-secondary:hover {
            color: white;
            background-color: var(--camp-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(148, 167, 174, 0.2);
        }

        .modal-footer .btn-outline-secondary:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(76, 107, 116, 0.15);
        }

        /* 狀態項容器 */
        .status-options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.5rem;
        }

        /* 狀態卡片基����樣��� */
        .status-card {
            position: relative;
            display: block;
            cursor: pointer;
            border-radius: 8px;
            border: 1px solid #eee;
            transition: all 0.2s ease;
            overflow: hidden;
            margin: 0;
        }

        /* 狀態卡片內容 */
        .status-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
        }

        /* 狀態圖標容器 */
        .status-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            font-size: 1.1rem;
            color: white;
        }

        /* 狀態文字 */
        .status-text {
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* 隱藏原始 radio */
        .status-radio {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        /* 待確認狀態 */
        #status_pending+.status-content {
            background-color: #F7F4ED;
            color: #B5A99A;
        }

        #status_pending+.status-content .status-icon {
            background-color: #D4C5A9;
        }

        /* 已確認狀態 */
        #status_confirmed+.status-content {
            background-color: #EDF5F1;
            color: #8FA99B;
        }

        #status_confirmed+.status-content .status-icon {
            background-color: #A8C2B3;
        }

        /* 已取消狀態 */
        #status_cancelled+.status-content {
            background-color: #F5EDED;
            color: #B59A9A;
        }

        #status_cancelled+.status-content .status-icon {
            background-color: #D4B5B5;
        }

        /* Hover 效 */
        .status-card:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        /* 選中效果 */
        .status-radio:checked+.status-content {
            border-left: 4px solid currentColor;
        }

        /* Modal 大小調整 */
        .modal-sm {
            max-width: 320px;
        }

        /* Modal 內容間距 */
        .modal-body {
            padding: 1rem;
        }

        /* 詳細訂單 Modal 樣式 */
        .booking-detail-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .detail-section {
            background-color: #fff;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            color: var(--camp-primary);
            font-weight: 600;
        }

        .section-header i {
            font-size: 1.2rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .detail-item label {
            color: var(--camp-secondary);
            font-size: 0.9rem;
            margin: 0;
        }

        .detail-item .value {
            color: var(--camp-text);
            font-size: 1rem;
        }

        .total-price .value {
            color: var(--camp-primary);
            font-size: 1.1rem;
        }

        /* Modal 整體樣式 */
        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* 
        .modal-header {
            padding: 1.5rem 2rem;
        } */

        .modal-body {
            background-color: var(--camp-light);
        }

        .modal-footer {
            padding: 1.25rem 2rem;
        }

        /* 關閉按鈕樣式 */
        .modal-footer .btn-outline-secondary {
            min-width: 120px;
            padding: 0.4rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        /* 狀態選項樣式 */
        .status-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .status-option {
            position: relative;
        }

        .status-option input[type="radio"] {
            display: none;
        }

        .status-label {
            display: block;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        /* 狀態顏色 */
        .status-label.pending {
            color: #6c757d;
        }

        .status-label.confirmed {
            color: #198754;
        }

        .status-label.cancelled {
            color: #dc3545;
        }

        /* 選中效果 */
        .status-option input[type="radio"]:checked+.status-label {
            border-color: currentColor;
            background-color: rgba(0, 0, 0, 0.05);
            font-weight: 500;
        }

        /* Hover 效果 */
        .status-label:hover {
            border-color: currentColor;
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* 狀態編輯 Modal 樣式 */
        .modal-content {
            border: none;
            border-radius: 16px;
            overflow: hidden;
        }

        /* .modal-header {
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
        } */

        .modal-title {
            font-size: 1.1rem;
            color: white;
        }

        .status-options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.5rem;
        }

        /* 狀態選項新設計 */
        .status-radio {
            position: relative;
            display: block;
            margin: 0;
            cursor: pointer;
        }

        .status-radio input {
            display: none;
        }

        .status-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            background-color: #f8f9fa;
            transition: all 0.2s ease;
        }

        .status-box i {
            font-size: 1.1rem;
        }

        /* 狀態顏 */
        .status-box.pending {
            color: #D4B499;
        }

        .status-box.confirmed {
            color: #A8B686;
        }

        .status-box.cancelled {
            color: #C4A4A4;
        }

        /* 選中效果 */
        .status-radio input:checked+.status-box {
            color: white;
        }

        .status-radio input:checked+.status-box.pending {
            background-color: #D4B499;
        }

        .status-radio input:checked+.status-box.confirmed {
            background-color: #A8B686;
        }

        .status-radio input:checked+.status-box.cancelled {
            background-color: #C4A4A4;
        }

        /* Hover 效果 */
        .status-box:hover {
            transform: translateX(4px);
            background-color: #f0f1f3;
        }

        .status-radio input:checked+.status-box:hover {
            transform: translateX(4px);
            opacity: 0.9;
        }

        /* Modal 按鈕 */
        .modal-footer {
            padding: 1rem;
            border-top: 1px solid #CBD5E1;
            background-color: #F8FAFC;
            gap: 1rem;
        }

        .modal-btn {
            padding: 0.5rem 1.25rem;
            font-size: 0.9rem;
            border-radius: 6px;
            min-width: 100px;
            max-width: 120px;
        }

        /* 狀態選項群組 */
        .status-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 0;
        }

        /* 狀態選項 */
        .status-item {
            width: auto;
            min-width: 160px;
        }

        .status-input {
            display: none;
        }

        /* 狀態標籤基本樣式 */
        .status-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.95rem;
            background-color: transparent;
        }

        /* 狀態顏色和效果 */
        #status_pending+.status-label {
            color: #D4B499;
            border: 1px solid #D4B499;
        }

        #status_pending+.status-label:hover,
        #status_pending:checked+.status-label {
            background-color: #D4B499;
            color: white;
        }

        /* 已確認狀態 */
        #status_confirmed+.status-label {
            color: #A8B686;
            border: 1px solid #A8B686;
        }

        #status_confirmed+.status-label:hover,
        #status_confirmed:checked+.status-label {
            background-color: #A8B686;
            color: white;
        }

        /* 已取消狀態 */
        #status_cancelled+.status-label {
            color: #C4A4A4;
            border: 1px solid #C4A4A4;
        }

        #status_cancelled+.status-label:hover,
        #status_cancelled:checked+.status-label {
            background-color: #C4A4A4;
            color: white;
        }

        /* 搜尋框樣式 */
        .search-box {
            position: relative;
            width: 450px;
            /* 增加寬度 */
        }

        .search-box input {
            padding-right: 35px;
            padding-left: 15px;
            /* 增加左邊內距 */
            border-radius: 20px;
            border: 1px solid var(--camp-border);
            height: 42px;
            /* 適當增加高度 */
            font-size: 0.95rem;
            /* 調整字體大小 */
        }

        .search-box .bi-search {
            position: absolute;
            right: 15px;
            /* 調整圖示位置 */
            top: 50%;
            transform: translateY(-50%);
            color: var(--camp-secondary);
            font-size: 1.1rem;
            /* 調整圖示大小 */
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            .search-box {
                width: 300px;
            }
        }

        @media (max-width: 576px) {
            .search-box {
                width: 100%;
                margin-top: 1rem;
            }

            /* 在手機版將標題和搜尋框改為垂直排列 */
            .d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                align-items: stretch !important;
            }

            .page-title {
                text-align: center;
                margin-bottom: 1rem;
            }
        }

        /* 排序圖示樣式 */
        .sort-icon {
            font-size: 0.8em;
            margin-left: 5px;
            opacity: 0.5;
            cursor: pointer;
        }

        th {
            cursor: pointer;
        }

        th:hover .sort-icon {
            opacity: 1;
        }

        /* 分頁樣式 */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 10px;
            min-height: 60px;
            /* 設定最小高度 */
        }

        .pagination-info {
            color: var(--camp-secondary);
        }

        .pagination {
            margin: 0;
            user-select: none;
            /* 防止文字被選中 */
        }

        .pagination .page-link {
            cursor: pointer;
            user-select: none;
            -webkit-user-drag: none;
            /* 防止拖曳 */
            color: var(--camp-primary);
            border-color: var(--camp-border);
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background-color: white;
            transition: all 0.2s ease;
        }

        .pagination .page-item.disabled .page-link {
            background-color: #e9ecef;
            /* 更明顯的禁用背景色 */
            border-color: #dee2e6;
            color: #adb5bd;
            /* 更淡的文字顏色 */
            cursor: not-allowed;
            opacity: 0.8;
            /* 增加透明度效果 */
            pointer-events: none;
            /* 確保完全禁用點擊 */
        }

        .pagination .page-item.active .page-link {
            background-color: var(--camp-primary);
            border-color: var(--camp-primary);
            color: white;
            font-weight: 500;
        }

        .pagination .page-link:hover:not(.disabled) {
            background-color: var(--camp-light);
            border-color: var(--camp-primary);
            color: var(--camp-primary);
        }

        /* 省略號樣式 */
        .pagination .page-item.disabled span.page-link {
            background-color: transparent;
            border: none;
        }

        /* 添加統計卡片的 active 狀態樣式 */
        .stat-card.active {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        /* 為每種類型的卡片添加 active 狀態樣式 */
        .stat-card.all.active {
            background: linear-gradient(135deg, var(--camp-primary) 0%, var(--camp-secondary) 100%);
        }

        .stat-card.pending.active {
            background: linear-gradient(135deg, var(--status-pending) 0%, #D4C5A9 100%);
        }

        .stat-card.confirmed.active {
            background: linear-gradient(135deg, var(--status-confirmed) 0%, #A8C2B3 100%);
        }

        .stat-card.cancelled.active {
            background: linear-gradient(135deg, var(--status-cancelled) 0%, #D4B5B5 100%);
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
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="page-container" style="margin-left: 360px;">
        <!-- 統計卡片 -->
        <div class="stats-container mb-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card all" data-status="all" onclick="filterBookings()">
                        <div class="stat-icon">
                            <i class="bi bi-collection"></i> <!-- 總訂單 -->
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="totalBookings"><?= $stats['total'] ?></div>
                            <div class="stat-label">訂單數</div>
                        </div>

                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card pending" data-status="pending" onclick="filterBookings('pending')">
                        <div class="stat-icon">
                            <i class="bi bi-hourglass-split"></i> <!-- 待確認 -->
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="pendingBookings"><?= $stats['pending'] ?></div>
                            <div class="stat-label">待確認</div>
                        </div>

                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card confirmed" data-status="confirmed" onclick="filterBookings('confirmed')">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i> <!-- 已確認 -->
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="confirmedBookings"><?= $stats['confirmed'] ?></div>
                            <div class="stat-label">已確認</div>
                        </div>

                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card cancelled" data-status="cancelled" onclick="filterBookings('cancelled')">
                        <div class="stat-icon">
                            <i class="bi bi-x-circle"></i> <!-- 已取消 -->
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="cancelledBookings"><?= $stats['cancelled'] ?></div>
                            <div class="stat-label">已取消</div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- 主要內容區 -->
        <div class="content-wrapper">
            <!-- 標題列 -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title m-0">訂單管理</h1>
                <div class="search-box">
                    <input type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="搜尋訂單編號、活動名稱、價格..."
                        onkeyup="handleSearch()">
                    <i class="bi bi-search"></i>
                </div>
            </div>

            <!-- 單列表 -->
            <div class="table-responsive mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th onclick="sortTable('booking_id')">
                                訂單編號 <i class="bi bi-arrow-down-up sort-icon"></i>
                            </th>
                            <th onclick="sortTable('activity_name')">
                                活動名稱 <i class="bi bi-arrow-down-up sort-icon"></i>
                            </th>
                            <th onclick="sortTable('spot_name')">
                                營位名稱 <i class="bi bi-arrow-down-up sort-icon"></i>
                            </th>
                            <th onclick="sortTable('user_name')">
                                預訂者 <i class="bi bi-arrow-down-up sort-icon"></i>
                            </th>
                            <th onclick="sortTable('quantity')">
                                數量 <i class="bi bi-arrow-down-up sort-icon"></i>
                            </th>
                            <th onclick="sortTable('unit_price')">
                                單價 <i class="bi bi-arrow-down-up sort-icon"></i>
                            </th>
                            <th onclick="sortTable('total_price')">
                                總價格 <i class="bi bi-arrow-down-up sort-icon"></i>
                            </th>
                            <th onclick="sortTable('status')">
                                狀態 <i class="bi bi-arrow-down-up sort-icon"></i>
                            </th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsList"></tbody>
                </table>
            </div>

            <!-- 加入分頁控制項 -->
            <div class="pagination-container">
                <div class="pagination-info">
                    顯示第 <span id="startIndex">1</span> 到第 <span id="endIndex">10</span> 筆，共 <span id="totalItems">0</span> 筆資料
                </div>
                <ul class="pagination" id="pagination"></ul>
            </div>
        </div>
    </div>

    <!-- 狀態編輯 Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>編輯訂單狀態
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="status-group">
                        <div class="status-item">
                            <input type="radio" name="bookingStatus" id="status_pending" value="pending" class="status-input">
                            <label for="status_pending" class="status-label">
                                <i class="bi bi-hourglass-split"></i>
                                <span>待確認</span>
                            </label>
                        </div>
                        <div class="status-item">
                            <input type="radio" name="bookingStatus" id="status_confirmed" value="confirmed" class="status-input">
                            <label for="status_confirmed" class="status-label">
                                <i class="bi bi-check-circle"></i>
                                <span>已確認</span>
                            </label>
                        </div>
                        <div class="status-item">
                            <input type="radio" name="bookingStatus" id="status_cancelled" value="cancelled" class="status-input">
                            <label for="status_cancelled" class="status-label">
                                <i class="bi bi-x-circle"></i>
                                <span>已取消</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary modal-btn" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary modal-btn" onclick="updateBookingStatus()">確認更新</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 訂單詳情 Modal -->
    <div class="modal fade"
        id="detailModal"
        tabindex="-1"
        role="dialog"
        aria-labelledby="detailModalLabel"
        aria-modal="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="detailModalLabel">訂單詳情</h5>
                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="關閉">
                    </button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="booking-detail-container">
                        <!-- 基本資訊 -->
                        <div class="detail-section">
                            <div class="section-header">
                                <i class="bi bi-info-circle"></i>
                                <span>基本資訊</span>
                            </div>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>訂單編號</label>
                                    <span id="bookingId" class="value"></span>
                                </div>
                                <div class="detail-item">
                                    <label>訂單狀態</label>
                                    <span id="bookingStatus" class="value"></span>
                                </div>
                                <div class="detail-item">
                                    <label>建立時間</label>
                                    <span id="createdAt" class="value"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 預訂資訊 -->
                        <div class="detail-section">
                            <div class="section-header">
                                <i class="bi bi-calendar-check"></i>
                                <span>預訂資</span>
                            </div>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>活動名稱</label>
                                    <span id="activityName" class="value"></span>
                                </div>
                                <div class="detail-item">
                                    <label>營位名稱</label>
                                    <span id="spotName" class="value"></span>
                                </div>
                                <div class="detail-item">
                                    <label>預訂數量</label>
                                    <span id="quantity" class="value"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 價格資訊 -->
                        <div class="detail-section">
                            <div class="section-header">
                                <i class="bi bi-currency-dollar"></i>
                                <span>價格資訊</span>
                            </div>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>單價</label>
                                    <span id="unitPrice" class="value"></span>
                                </div>
                                <div class="detail-item total-price">
                                    <label>總金額</label>
                                    <span id="totalPrice" class="value fw-bold"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">關閉</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 全局變數
        let detailModal = null;
        let statusModal = null;
        let currentBookingData = {};
        let currentBookingId = null;
        let allBookings = [];

        // 分頁相關變數
        let currentPage = 1;
        const itemsPerPage = 10;
        let sortField = 'booking_id';
        let sortDirection = 'asc';
        let filteredBookings = [];

        // 初始化所有 Modal
        function initializeModals() {
            console.log('Initializing modals...'); // 除錯用

            // 初始化狀態編輯 Modal
            const statusModalElement = document.getElementById('statusModal');
            if (statusModalElement) {
                statusModal = new bootstrap.Modal(statusModalElement);
                console.log('Status modal initialized'); // 除錯用

                // 添加更新按鈕事件監聽
                const updateBtn = document.getElementById('updateStatusBtn');
                if (updateBtn) {
                    updateBtn.addEventListener('click', updateBookingStatus);
                }
            } else {
                console.error('Status modal element not found'); // 除錯用
            }

            // 初始化詳細訂單 Modal
            const detailModalElement = document.getElementById('detailModal');
            if (detailModalElement) {
                detailModal = new bootstrap.Modal(detailModalElement);
                console.log('Detail modal initialized'); // 除錯用
            } else {
                console.error('Detail modal element not found'); // 除錯用
            }
        }


        // 確保 DOM 完全載入後再初始化
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded'); // 除錯用
            initializeModals();
            loadBookings();
        });

        // 顯示狀態編輯 Modal
        function showStatusModal(bookingId) {
            if (!statusModal) return;

            const booking = currentBookingData[bookingId];
            if (!booking) return;

            currentBookingId = bookingId;

            // 設置當前選中的狀態
            const statusRadio = document.querySelector(`input[name="bookingStatus"][value="${booking.status}"]`);
            if (statusRadio) {
                statusRadio.checked = true;
            }

            statusModal.show();
        }

        // 價格格式化函數
        function formatPrice(price) {
            // 如果價格包含逗號，先移除
            if (typeof price === 'string') {
                price = price.replace(/,/g, '');
            }

            // 轉換為數字
            const numPrice = Number(price);

            // 檢是否為有效數字
            if (isNaN(numPrice)) {
                console.error('Invalid price value:', price);
                return 'NT$ 0';
            }

            // 格式化價格
            return `NT$ ${numPrice.toLocaleString('zh-TW')}`;
        }

        // 顯示訂單詳情
        function showBookingDetail(bookingId) {
            if (!detailModal) return;
            const booking = currentBookingData[bookingId];
            if (!booking) return;

            // 除錯用
            console.log('Booking data:', booking);
            console.log('Unit price:', booking.unit_price);
            console.log('Total price:', booking.total_price);

            try {
                // 填充詳細資料
                document.getElementById('bookingId').textContent = booking.booking_id;
                document.getElementById('bookingStatus').innerHTML = getStatusBadge(booking.status);
                document.getElementById('createdAt').textContent = formatDate(booking.created_at);
                document.getElementById('activityName').textContent = booking.activity_name;
                document.getElementById('spotName').textContent = booking.spot_name;
                document.getElementById('quantity').textContent = booking.quantity;

                // 處理價格顯示
                const unitPrice = parseFloat(booking.unit_price) || 0;
                const totalPrice = parseFloat(booking.total_price) || 0;

                document.getElementById('unitPrice').textContent = formatPrice(unitPrice);
                document.getElementById('totalPrice').textContent = formatPrice(totalPrice);
            } catch (error) {
                console.error('Error displaying booking details:', error);
            }

            detailModal.show();
        }

        // 載入訂單列表
        async function loadBookings() {
            try {
                const response = await axios.get('/CampExplorer/owner/api/booking/get_bookings.php');
                if (response.data.success) {
                    allBookings = response.data.bookings;
                    filteredBookings = [...allBookings];
                    currentBookingData = {};
                    updateTotalStats();
                    updatePagination();
                    renderBookings(getCurrentPageBookings());
                }
            } catch (error) {
                console.error('Error loading bookings:', error);
                await Swal.fire({
                    title: '錯誤',
                    text: '無法載入訂單列表',
                    icon: 'error'
                });
            }
        }

        // 渲染訂單列表
        function renderBookings(bookings) {
            const bookingsList = document.getElementById('bookingsList');
            if (!bookingsList) return;

            bookingsList.innerHTML = '';

            bookings.forEach(booking => {
                currentBookingData[booking.booking_id] = booking;

                // 處理價格，移除可能的逗號
                const unitPrice = booking.unit_price.toString().replace(/,/g, '');
                const totalPrice = booking.total_price.toString().replace(/,/g, '');

                const row = document.createElement('tr');
                row.style.cursor = 'pointer';
                row.onclick = () => showBookingDetail(booking.booking_id);
                row.innerHTML = `
                    <td>${booking.booking_id}</td>
                    <td>${booking.activity_name}</td>
                    <td>${booking.spot_name}</td>
                    <td>${booking.user_name}</td>
                    <td>${booking.quantity}</td>
                    <td>${formatPrice(unitPrice)}</td>
                    <td>${formatPrice(totalPrice)}</td>
                    <td>${getStatusBadge(booking.status)}</td>
                    <td>
                        <button type="button" 
                            class="btn btn-outline-primary btn-sm" 
                            onclick="event.stopPropagation(); showStatusModal(${booking.booking_id})">
                            編輯狀態
                        </button>
                    </td>
                `;
                bookingsList.appendChild(row);
            });
        }

        // 確保 DOM 完全載入後再初始化
        document.addEventListener('DOMContentLoaded', function() {
            console.log('頁面載入完成');
            initializeModals();
            loadBookings();
        });

        // 更新訂單狀態
        async function updateBookingStatus() {
            if (!currentBookingId) {
                console.error('No booking selected');
                return;
            }

            const selectedStatus = document.querySelector('input[name="bookingStatus"]:checked');
            if (!selectedStatus) {
                await Swal.fire({
                    title: '錯誤',
                    text: '請選擇一個狀態',
                    icon: 'warning'
                });
                return;
            }

            try {
                const response = await axios.post('/CampExplorer/owner/api/booking/update_booking_status.php', {
                    booking_id: currentBookingId,
                    status: selectedStatus.value
                });

                if (response.data.success) {
                    statusModal.hide();
                    await loadBookings();
                    await Swal.fire({
                        title: '成功',
                        text: '訂單狀態已更新',
                        icon: 'success',
                        confirmButtonColor: '#6c757d'
                    });
                } else {
                    throw new Error(response.data.message || '更新失敗');
                }
            } catch (error) {
                console.error('Error updating booking status:', error);
                await Swal.fire({
                    title: '錯誤',
                    text: error.response?.data?.message || '無法更新訂單狀態',
                    icon: 'error'
                });
            }
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
                'pending': ['var(--status-pending)', 'hourglass-split', '待確認'],
                'confirmed': ['var(--status-confirmed)', 'check-circle', '已確認'],
                'cancelled': ['var(--status-cancelled)', 'x-circle', '已取消']
            };

            const [color, icon, text] = statusMap[status] || ['var(--camp-gray)', 'question-circle', '未知'];
            return `<span class="badge" style="background-color: ${color}"><i class="bi bi-${icon} me-1"></i>${text}</span>`;
        }

        // 更新總體統計數字
        function updateTotalStats() {
            const stats = {
                total: allBookings.length,
                pending: allBookings.filter(booking => booking.status === 'pending').length,
                confirmed: allBookings.filter(booking => booking.status === 'confirmed').length,
                cancelled: allBookings.filter(booking => booking.status === 'cancelled').length
            };

            // 更新統計卡片的數字
            document.getElementById('totalBookings').textContent = stats.total;
            document.getElementById('pendingBookings').textContent = stats.pending;
            document.getElementById('confirmedBookings').textContent = stats.confirmed;
            document.getElementById('cancelledBookings').textContent = stats.cancelled;
        }

        // 修改 filterBookings 函數
        function filterBookings(status = null) {
            console.log('Filtering by status:', status);

            // 移除所有卡片的 active 狀態
            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.remove('active');
            });

            // 為當前選中的卡片添加 active 狀態
            const selectedCard = document.querySelector(`.stat-card[data-status="${status || 'all'}"]`);
            if (selectedCard) {
                selectedCard.classList.add('active');
            }

            // 如果沒有訂單數據，直接返回
            if (!allBookings) {
                console.log('No bookings data available');
                return;
            }

            // 根據狀態篩選訂單
            filteredBookings = !status || status === 'all' ?
                [...allBookings] :
                allBookings.filter(booking => booking.status === status);

            console.log('Filtered bookings:', filteredBookings);

            // 重置分頁到第一頁
            currentPage = 1;

            // 更新分頁和渲染訂單
            updatePagination();
            renderBookings(getCurrentPageBookings());
        }

        // 確保頁面載入時預設選中"全部"卡片
        document.addEventListener('DOMContentLoaded', function() {
            console.log('頁面載入完成');
            initializeModals();
            loadBookings();

            // 預設選中"全部"卡片
            document.querySelector('.stat-card.all').classList.add('active');
        });

        // 搜尋功能增強
        function handleSearch() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();

            filteredBookings = allBookings.filter(booking => {
                // 移除價格中的所有非數字字符（包括 NT$ 和逗號）
                const unitPrice = parseFloat(booking.unit_price.toString().replace(/[^0-9.-]+/g, ''));
                const totalPrice = parseFloat(booking.total_price.toString().replace(/[^0-9.-]+/g, ''));

                // 將搜尋詞也清理為純數字（如果是數字的話）
                const searchNumber = parseFloat(searchTerm.replace(/[^0-9.-]+/g, ''));
                const isNumericSearch = !isNaN(searchNumber);

                // 價格範圍搜尋（支援大於、小於、等於）
                let priceMatch = false;
                if (isNumericSearch) {
                    if (searchTerm.startsWith('>=')) {
                        priceMatch = unitPrice >= searchNumber || totalPrice >= searchNumber;
                    } else if (searchTerm.startsWith('<=')) {
                        priceMatch = unitPrice <= searchNumber || totalPrice <= searchNumber;
                    } else if (searchTerm.startsWith('>')) {
                        priceMatch = unitPrice > searchNumber || totalPrice > searchNumber;
                    } else if (searchTerm.startsWith('<')) {
                        priceMatch = unitPrice < searchNumber || totalPrice < searchNumber;
                    } else {
                        // 精確匹配或包含匹配
                        priceMatch =
                            unitPrice === searchNumber ||
                            totalPrice === searchNumber ||
                            unitPrice.toString().includes(searchTerm) ||
                            totalPrice.toString().includes(searchTerm);
                    }
                }

                // 狀態搜尋轉換（支援中文搜尋）
                const statusMap = {
                    '待確認': 'pending',
                    '已確認': 'confirmed',
                    '已取消': 'cancelled'
                };
                const searchStatus = statusMap[searchTerm] || searchTerm;

                // 一般文字搜尋
                const textMatch =
                    booking.booking_id.toString().includes(searchTerm) ||
                    booking.activity_name.toLowerCase().includes(searchTerm) ||
                    booking.spot_name.toLowerCase().includes(searchTerm) ||
                    booking.user_name.toLowerCase().includes(searchTerm) ||
                    booking.status.toLowerCase().includes(searchStatus) ||
                    booking.quantity.toString().includes(searchTerm);

                return textMatch || priceMatch;
            });

            currentPage = 1;
            updatePagination();
            renderBookings(getCurrentPageBookings());
        }

        // 更新搜尋框提示文字

        // 排序功能
        function sortTable(field) {
            if (sortField === field) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortField = field;
                sortDirection = 'asc';
            }

            filteredBookings.sort((a, b) => {
                let valueA = a[field];
                let valueB = b[field];

                // 數字類型特殊處理
                if (field === 'unit_price' || field === 'total_price' || field === 'quantity') {
                    valueA = parseFloat(valueA);
                    valueB = parseFloat(valueB);
                }

                if (valueA < valueB) return sortDirection === 'asc' ? -1 : 1;
                if (valueA > valueB) return sortDirection === 'asc' ? 1 : -1;
                return 0;
            });

            renderBookings(getCurrentPageBookings());
        }

        // 獲取當前頁面的訂單
        function getCurrentPageBookings() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            return filteredBookings.slice(startIndex, endIndex);
        }

        // 更新分頁控制項
        function updatePagination() {
            const totalPages = Math.ceil(filteredBookings.length / itemsPerPage);
            const startIndex = (currentPage - 1) * itemsPerPage + 1;
            const endIndex = Math.min(startIndex + itemsPerPage - 1, filteredBookings.length);

            document.getElementById('startIndex').textContent = startIndex;
            document.getElementById('endIndex').textContent = endIndex;
            document.getElementById('totalItems').textContent = filteredBookings.length;

            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            // 上一頁按鈕
            const prevLi = document.createElement('li');
            const isFirstPage = currentPage === 1;
            prevLi.className = `page-item ${isFirstPage ? 'disabled' : ''}`;
            prevLi.innerHTML = `
                <a class="page-link ${isFirstPage ? 'text-muted' : ''}" 
                   href="javascript:void(0)" 
                   onclick="return changePage(${currentPage - 1}, event)">
                    <i class="bi bi-chevron-left"></i> 上一頁
                </a>`;
            pagination.appendChild(prevLi);

            // 頁碼按鈕
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);

            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }

            // 第一頁和省略號
            if (startPage > 1) {
                pagination.appendChild(createPageItem(1));
                if (startPage > 2) {
                    const ellipsis = document.createElement('li');
                    ellipsis.className = 'page-item disabled';
                    ellipsis.innerHTML = '<span class="page-link">...</span>';
                    pagination.appendChild(ellipsis);
                }
            }

            // 中間頁碼
            for (let i = startPage; i <= endPage; i++) {
                pagination.appendChild(createPageItem(i));
            }

            // 最後頁和省略號
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsis = document.createElement('li');
                    ellipsis.className = 'page-item disabled';
                    ellipsis.innerHTML = '<span class="page-link">...</span>';
                    pagination.appendChild(ellipsis);
                }
                pagination.appendChild(createPageItem(totalPages));
            }

            // 下一頁按鈕
            const nextLi = document.createElement('li');
            const isLastPage = currentPage === totalPages;
            nextLi.className = `page-item ${isLastPage ? 'disabled' : ''}`;
            nextLi.innerHTML = `
                <a class="page-link ${isLastPage ? 'text-muted' : ''}" 
                   href="javascript:void(0)" 
                   onclick="return changePage(${currentPage + 1}, event)">
                    下一頁 <i class="bi bi-chevron-right"></i>
                </a>`;
            pagination.appendChild(nextLi);
        }

        // 創建分頁按鈕
        function createPageItem(pageNumber) {
            const li = document.createElement('li');
            li.className = `page-item ${currentPage === pageNumber ? 'active' : ''}`;
            li.innerHTML = `
                <a class="page-link" 
                   href="javascript:void(0)" 
                   onclick="changePage(${pageNumber}, event)">
                    ${pageNumber}
                </a>`;
            return li;
        }

        // 切換頁面
        function changePage(page, event) {
            // 阻止所有預設行為
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            if (page < 1 || page > Math.ceil(filteredBookings.length / itemsPerPage)) return;

            currentPage = page;
            updatePagination();
            renderBookings(getCurrentPageBookings());

            return false; // 確保不會觸發任何預設行為
        }
    </script>
</body>

</html>