<?php
session_start();

// 檢查是否有管理員登入狀態
$isAdminLoggedIn = isset($_SESSION['admin_id']);

// 清除所有 session 資料
session_destroy();

// 設定正確的路徑
$adminLoginPath = "login.php";  // 使用相對路徑
$portalPath = "/CampExplorer/portal.php";  // 使用絕對路徑
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登出系統</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <script>
        Swal.fire({
            title: '登出成功',
            text: '感謝您的使用！',
            icon: 'success',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            Swal.fire({
                title: '您要？',
                icon: 'question',
                showDenyButton: true,
                confirmButtonText: '重新登入',
                denyButtonText: '回到入口頁',
                confirmButtonColor: '#28a745',
                denyButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo $adminLoginPath; ?>';
                } else {
                    window.location.href = '<?php echo $portalPath; ?>';
                }
            });
        });
    </script>
</body>
</html>