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
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&family=Poppins:wght@400;500;600&family=Montserrat:wght@500;600&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --font-primary: 'Poppins', 'Noto Sans TC', sans-serif;
            --font-heading: 'Montserrat', 'Noto Sans TC', sans-serif;
        }

        body {
            font-family: var(--font-primary);
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading);
            font-weight: 600;
            letter-spacing: 0.5px;
        }
    </style>
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
                title: '<span style="color: #94A89A; font-size: 1.8rem; font-weight: 600;">您要？</span>',
                icon: 'question',
                showDenyButton: true,
                confirmButtonText: '重新登入',
                denyButtonText: '回到入口頁',
                confirmButtonColor: '#94A89A',    // 莫蘭迪淺綠
                denyButtonColor: '#B5B5BC',       // 莫蘭迪灰
                padding: '2em',
                width: '32em',
                background: '#F5F5F5',            // 淺灰背景色
                backdrop: `rgba(148, 168, 154, 0.2)`,  // 莫蘭迪淺綠半透明背景
                didOpen: () => {
                    // 自定義 question icon 顏色
                    const icon = Swal.getIcon();
                    icon.style.color = '#94A89A';
                    icon.style.borderColor = '#B5B5BC';
                    
                    // 自定義按鈕樣式
                    const confirmBtn = Swal.getConfirmButton();
                    const denyBtn = Swal.getDenyButton();
                    
                    [confirmBtn, denyBtn].forEach(btn => {
                        if (btn) {
                            Object.assign(btn.style, {
                                borderRadius: '8px',
                                padding: '12px 28px',
                                fontSize: '1.1rem',
                                fontWeight: '500',
                                boxShadow: '0 2px 6px rgba(0,0,0,0.15)',
                                margin: '0 8px',
                                letterSpacing: '0.5px'
                            });
                        }
                    });
                }
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