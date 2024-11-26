<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>露營趣 | 入口選擇</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            font-family: 'Segoe UI', 'Microsoft JhengHei', sans-serif;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .portal-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .portal-content {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 900px;
            width: 100%;
        }

        .portal-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .portal-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .portal-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .portal-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            justify-content: center;
            margin: 0 auto;
            max-width: 1200px;
        }

        .portal-card {
            flex: none;
            width: 100%;
            min-width: 0;
            max-width: none;
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .portal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .portal-card .icon {
            width: 80px;
            height: 80px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            transition: all 0.3s ease;
        }

        .portal-card:hover .icon {
            background: #2c3e50;
            color: white;
        }

        .portal-card h3 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .portal-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .portal-card .btn {
            background: #2c3e50;
            color: white;
            border-radius: 25px;
            padding: 8px 25px;
            transition: all 0.3s ease;
        }

        .portal-card:hover .btn {
            background: #34495e;
            transform: scale(1.05);
        }

        .copyright {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 30px;
            font-size: 0.9rem;
        }

        @media (max-width: 992px) {
            .portal-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .portal-cards {
                grid-template-columns: 1fr;
            }
            
            .portal-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="portal-content">
            <div class="portal-header">
                <h1>露營趣 CampingFun</h1>
                <p>選擇您要進入的平台</p>
            </div>
            
            <div class="portal-cards">
                <!-- 前台入口 -->
                <a href="member/index.php" class="portal-card">
                    <div class="icon">
                        <i class="bi bi-house-heart"></i>
                    </div>
                    <h3>前台首頁</h3>
                    <p>瀏覽營地、預訂營位<br>會員註冊與登入</p>
                    <button class="btn">前往首頁</button>
                </a>

                <!-- 營主後台入口 -->
                <a href="owner/owner-login.php" class="portal-card">
                    <div class="icon">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h3>營主後台</h3>
                    <p>營地管理、營位管理<br>訂單與收益分析</p>
                    <button class="btn">營主登入</button>
                </a>

                <!-- 系統後台入口 -->
                <a href="admin/login.php" class="portal-card">
                    <div class="icon">
                        <i class="bi bi-gear"></i>
                    </div>
                    <h3>系統後台</h3>
                    <p>平台管理、審核管理<br>系統維運中心</p>
                    <button class="btn">管理員登入</button>
                </a>
            </div>

            <div class="copyright">
                © 2024 CampingFun. All rights reserved.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>