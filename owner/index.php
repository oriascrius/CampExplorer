<?php
session_start();
if (!isset($_SESSION['owner_id'])) {
    header("Location: owner-login.php");
    exit;
}

// 預設頁面
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// 定義允許的頁面路徑映射
$pageMap = [
    'dashboard' => 'pages/dashboard.php',
    'camp_add' => 'pages/camp/camp-add.php',
    'camp_list' => 'pages/camp/camp-list.php',
    'camp_edit' => 'pages/camp/camp-edit.php',
    'camp_status' => 'pages/campStatus/camp-status.php',
    'spot_add' => 'pages/spot/spot-add.php',
    'spot_list' => 'pages/spot/spot-list.php',
    'activity_list' => 'pages/activity/activity_list.php',
    'booking_list' => 'pages/booking/booking-list.php'
];

// 檢查頁面是否存在
$pagePath = isset($pageMap[$page]) ? $pageMap[$page] : 'pages/404.php';
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>營主後台 - Camp Explorer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="/CampExplorer/owner/includes/style.css" rel="stylesheet">
    <link href="/CampExplorer/owner/includes/pages-common.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-wrapper">
            <div id="mainContent" class="main-content">
                <?php include $pagePath; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script src="js/navigation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navigation = new Navigation();
        });
    </script>
</body>

</html>