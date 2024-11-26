<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - 頁面未找到</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="/CampExplorer/owner/includes/style.css" rel="stylesheet">
    <style>
        :root {
            --camp-primary: #4C6B74;
            --camp-secondary: #94A7AE;
            --camp-light: #F5F7F8;
            --camp-text: #2A4146;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--camp-light);
            font-family: 'Segoe UI', 'Microsoft JhengHei', sans-serif;
        }

        .error-container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-code {
            font-size: 8rem;
            font-weight: bold;
            color: var(--camp-primary);
            margin: 0;
            line-height: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .error-message {
            font-size: 1.5rem;
            color: var(--camp-text);
            margin: 1rem 0 2rem;
        }

        .error-description {
            color: var(--camp-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .camping-icon {
            font-size: 4rem;
            color: var(--camp-primary);
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .btn-back {
            display: inline-block;
            padding: 0.8rem 2rem;
            background-color: var(--camp-primary);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(76, 107, 116, 0.2);
        }

        .btn-back:hover {
            background-color: var(--camp-text);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 107, 116, 0.3);
        }

        .btn-back i {
            margin-right: 0.5rem;
        }

        /* 裝飾元素 */
        .decoration {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: -1;
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            background-color: var(--camp-primary);
            opacity: 0.1;
        }

        .circle-1 {
            width: 200px;
            height: 200px;
            top: -100px;
            left: -100px;
        }

        .circle-2 {
            width: 150px;
            height: 150px;
            bottom: -75px;
            right: -75px;
        }
    </style>
</head>

<body>
    <!-- 裝飾背景 -->
    <div class="decoration">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>

    <div class="error-container">
        <i class="fas fa-campground camping-icon"></i>
        <h1 class="error-code">404</h1>
        <h2 class="error-message">糟糕！頁面迷路了</h2>
        <p class="error-description">
            看來您要找的頁面已經去露營了！<br>
            別擔心，讓我們一起回到營地吧。
        </p>
        <a href="/CampExplorer/portal.php" class="btn-back">
            <i class="fas fa-home"></i>返回首頁
        </a>
    </div>

    <script>
        // 添加滑鼠移動視差效果
        document.addEventListener('mousemove', (e) => {
            const circles = document.querySelectorAll('.circle');
            const centerX = window.innerWidth / 2;
            const centerY = window.innerHeight / 2;
            
            circles.forEach((circle, index) => {
                const speed = (index + 1) * 0.03;
                const x = (e.clientX - centerX) * speed;
                const y = (e.clientY - centerY) * speed;
                
                circle.style.transform = `translate(${x}px, ${y}px)`;
            });
        });
    </script>
</body>

</html>