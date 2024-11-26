<?php
session_start();

// 檢查是否有營主登入狀態
$isOwnerLoggedIn = isset($_SESSION['owner_id']);

// 清除所有 session 資料
session_destroy();

// 設定正確的路徑
$ownerAuthPath = "owner-login.php";  // 更新營主登入頁面路徑
$portalPath = "../portal.php";      // 入口頁面
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登出系統</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #2B4865;
            --primary-light: #256D85;
            --accent: #8FE3CF;
            --accent-light: #A5F1E9;
            --hover: #7FBCD2;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: radial-gradient(
                circle at top right,
                var(--primary-light),
                var(--primary)
            );
            font-family: 'Noto Sans TC', sans-serif;
        }

        /* 背景動畫效果 */
        .camping-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .aurora {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            opacity: 0.3;
            animation: aurora-rotation 120s linear infinite;
        }

        .aurora__item {
            position: absolute;
            background: linear-gradient(
                90deg,
                rgba(143, 227, 207, 0.2),
                rgba(37, 109, 133, 0.2),
                rgba(143, 227, 207, 0.2)
            );
            filter: blur(60px);
            border-radius: 100%;
            animation: aurora-wave 20s ease infinite;
        }

        @keyframes aurora-rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes aurora-wave {
            0%, 100% {
                transform: scale(1) translate(0, 0);
                border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            }
            50% {
                transform: scale(0.9) translate(2%, -2%);
                border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%;
            }
        }
    </style>
</head>
<body>
    <!-- 背景動畫 -->
    <div class="camping-bg">
        <div class="aurora">
            <div class="aurora__item"></div>
            <div class="aurora__item"></div>
        </div>
    </div>

    <script>
        // 自定義 SweetAlert2 全域樣式
        const swalCustomStyle = Swal.mixin({
            customClass: {
                container: 'swal-container',
                popup: 'swal-popup',
                title: 'swal-title',
                htmlContainer: 'swal-html',
                confirmButton: 'swal-button',
                denyButton: 'swal-button',
            },
            buttonsStyling: true,
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: `rgba(43, 72, 101, 0.4)`,
        });

        // 登出成功提示 - 簡化第一個視窗
        Swal.fire({
            title: '登出成功',
            text: '感謝您的使用！',
            icon: 'success',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            <?php if ($isOwnerLoggedIn): ?>
                swalCustomStyle.fire({
                    title: '<span style="color: #2B4865; font-size: 1.8rem; font-weight: 600;">您要？</span>',
                    icon: 'question',
                    showDenyButton: true,
                    confirmButtonText: '重新登入',
                    denyButtonText: '回到入口頁',
                    padding: '2em',
                    width: '32em',
                    confirmButtonColor: '#256D85',
                    denyButtonColor: '#7FBCD2',
                    reverseButtons: true,
                    didOpen: () => {
                        // 自定義 question icon 顏色
                        const icon = Swal.getIcon();
                        icon.style.color = '#256D85';
                        icon.style.borderColor = '#8FE3CF';
                        
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
                                    textTransform: 'none',
                                    letterSpacing: '0.5px'
                                });
                            }
                        });

                        // 調整按鈕容器樣式
                        const actions = document.querySelector('.swal2-actions');
                        if (actions) {
                            Object.assign(actions.style, {
                                marginTop: '2rem',
                                width: '100%',
                                justifyContent: 'center',
                                gap: '1rem'
                            });
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '<?php echo $ownerAuthPath; ?>';
                    } else {
                        window.location.href = '<?php echo $portalPath; ?>';
                    }
                });
            <?php else: ?>
                window.location.href = '<?php echo $portalPath; ?>';
            <?php endif; ?>
        });

        // 添加全域 CSS 樣式
        const style = document.createElement('style');
        style.textContent = `
            .swal2-popup {
                border-radius: 16px !important;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1) !important;
            }
            
            .swal2-title {
                margin-bottom: 0.5em !important;
                padding: 0 !important;
                line-height: 1.3 !important;
            }
            
            .swal2-html-container {
                margin: 0.5em 0 !important;
                line-height: 1.5 !important;
            }
            
            .swal2-icon {
                margin: 1.5em auto 1em !important;
                transform: scale(1.2) !important;
            }
            
            .swal2-actions {
                margin-top: 2em !important;
            }
            
            .swal-button {
                transition: all 0.3s ease !important;
            }
            
            .swal-button:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
            }
        `;
        document.head.appendChild(style);

        // 背景動畫效果
        document.addEventListener('mousemove', function(e) {
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            document.querySelector('.aurora').style.transform = 
                `translate(${x * 30}px, ${y * 30}px) rotate(${x * y * 360}deg)`;
        });
    </script>
</body>
</html>
