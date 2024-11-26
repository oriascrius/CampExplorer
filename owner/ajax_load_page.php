<?php
session_start();

// 檢查是否登入
if (!isset($_SESSION['owner_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// 獲取請求的頁面
$page = $_GET['page'] ?? 'dashboard';

// 頁面映射（參考 index.php 的映射）
$pageMap = [
    'dashboard' => 'pages/dashboard.php',
    'camp_add' => 'pages/camp/camp-add.php',
    'camp_list' => 'pages/camp/camp-list.php',
    'camp_edit' => 'pages/camp/camp-edit.php',
    'camp_status' => 'pages/campStatus/camp-status.php',
    'spot_add' => 'pages/spot/spot-add.php',
    'spot_list' => 'pages/spot/spot-list.php',
    'activity_list' => 'pages/activity/activity_list.php'
];

// 檢查頁面是否存在
if (!isset($pageMap[$page])) {
    http_response_code(404);
    exit('Page not found');
}

// 包含目標頁面
include __DIR__ . '/' . $pageMap[$page];