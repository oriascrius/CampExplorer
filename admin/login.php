<?php
session_start();
require_once __DIR__ . '/../camping_db.php';

// 關閉錯誤輸出但保留日誌
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// 檢查是否已經登入
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// 如果是 AJAX 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    try {
        // 使用全局連接
        global $db;
        $conn = $db;
        
        // 處理登入請求
        if (isset($_POST['action']) && $_POST['action'] === 'login') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            // 基本驗證
            if (empty($username) || empty($password)) {
                throw new Exception('請輸入帳號和密碼');
            }

            // 檢查帳號
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || $admin['status'] != 1) {
                throw new Exception('帳號或密碼錯誤');
            }

            // 直接比對密碼
            if ($password !== $admin['password']) {
                throw new Exception('帳號或密碼錯誤');
            }

            // 更新登入資訊
            $stmt = $conn->prepare("UPDATE admins SET login_at = NOW(), login_ip = ? WHERE id = ?");
            $stmt->execute([$_SERVER['REMOTE_ADDR'], $admin['id']]);

            // 登入成功，設置 session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];

            echo json_encode([
                'success' => true,
                'message' => '登入成功！'
            ]);
            exit;
        }

        // 處理註冊請求
        if (isset($_POST['action']) && $_POST['action'] === 'register') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            // 基本驗證
            if (empty($username) || empty($password) || empty($name) || empty($email)) {
                throw new Exception('所有欄位都必須填寫');
            }

            if ($password !== $confirm_password) {
                throw new Exception('密碼不一致');
            }

            // 檢查帳號是否已存在
            $stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('帳號已存在');
            }

            // 新增管理員（不加密密碼）
            $stmt = $conn->prepare("INSERT INTO admins (username, password, name, email, role, status) VALUES (?, ?, ?, ?, 1, 1)");
            $success = $stmt->execute([
                $username,
                $password,  // 直接使用原始密碼
                $name,
                $email
            ]);

            if (!$success) {
                throw new Exception('註冊失敗，請稍後再試');
            }

            echo json_encode([
                'success' => true,
                'message' => '註冊成功，請登入'
            ]);
            exit;
        }

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '系統錯誤，請稍後再試'
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// 移除第108行的 < 符號，直接開始 HTML 內容
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員登入 - 露營趣</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        /* 引入 Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&family=Poppins:wght@400;500;600&display=swap');

        /* 基礎字體設置 */
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(-45deg, #1a2236, #2c3e50, #1a4a5e, #2c3e50);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            position: relative;
            overflow: hidden;
            font-family: 'Noto Sans TC', 'Poppins', sans-serif;
            letter-spacing: 0.5px;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        /* 添加浮動粒子效果 */
        body::before,
        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        body::before {
            top: -150px;
            left: -150px;
        }

        body::after {
            bottom: -150px;
            right: -150px;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0);
            }
            25% {
                transform: translate(100px, 100px);
            }
            50% {
                transform: translate(0, 200px);
            }
            75% {
                transform: translate(-100px, 100px);
            }
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: flex;
            min-height: 600px;
        }

        .auth-image {
            flex: 1;
            background: linear-gradient(135deg, #1a4a5e 0%, #2c3e50 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: white;
            text-align: center;
        }

        .auth-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="rgba(255,255,255,0.05)" fill-rule="evenodd"/%3E%3C/svg%3E');
            opacity: 0.8;
        }

        .auth-image-content {
            position: relative;
            z-index: 1;
        }

        .auth-image-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            font-weight: 300;
        }

        .auth-image-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 80%;
            margin: 0 auto;
            line-height: 1.6;
        }

        .auth-content {
            flex: 1;
            padding: 3rem;
            position: relative;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            font-weight: 700;
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .auth-header p {
            font-size: 1.1rem;
            color: #7f8c8d;
            font-weight: 300;
            letter-spacing: 0.8px;
        }

        .auth-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 2px solid #eee;
        }

        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 1rem;
            cursor: pointer;
            color: #6c757d;
            position: relative;
            transition: all 0.3s ease;
        }

        .auth-tab.active {
            color: #3498db;
        }

        .auth-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #3498db;
        }

        .auth-form {
            position: absolute;
            width: 100%;
            opacity: 0;
            visibility: hidden;
            transform: rotateY(180deg);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            backface-visibility: hidden;
        }

        .auth-form.active {
            position: relative;
            opacity: 1;
            visibility: visible;
            transform: rotateY(0deg);
        }

        .auth-form.slide-left {
            transform: rotateY(-180deg);
        }

        .auth-form.slide-right {
            transform: rotateY(180deg);
        }

        .form-floating input {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .form-floating input:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-color: #3498db;
        }

        .btn-auth {
            position: relative;
            overflow: hidden;
            z-index: 1;
            background: linear-gradient(45deg, #2c3e50, #3498db);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-auth::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #3498db, #2c3e50);
            transition: all 0.5s ease;
            z-index: -1;
        }

        .btn-auth:hover::before {
            left: 0;
        }

        .alert {
            margin-bottom: 1rem;
            text-align: center;
            padding: 0.75rem;
            border-radius: 8px;
        }

        /* 返回首頁按鈕樣式 */
        .back-to-home {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-to-home:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .back-to-home i {
            font-size: 1.1rem;
        }

        /* 添加動畫效果 */
        .back-to-home {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* 響應式調整 */
        @media (max-width: 768px) {
            .back-to-home {
                top: 10px;
                left: 10px;
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }

        /* 標籤切換動畫 */
        .auth-tab {
            position: relative;
            overflow: hidden;
        }

        .auth-tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 2px;
            background: #3498db;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .auth-tab:hover::after {
            width: 100%;
        }

        .auth-tab.active::after {
            width: 100%;
        }

        /* 添加背景粒子效果 */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        /* 表單容器樣式優化 */
        .auth-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2.5rem;
        }

        /* 輸入框樣式優化 */
        .form-floating input {
            border: 2px solid rgba(52, 152, 219, 0.1);
            border-radius: 12px;
            padding: 1rem 1rem;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-floating input:focus {
            transform: translateY(-2px);
            border-color: #3498db;
            box-shadow: 0 8px 16px rgba(52, 152, 219, 0.15);
            background: white;
        }

        .form-floating label {
            padding: 1rem;
            color: #7f8c8d;
        }

        /* 按鈕樣式優化 */
        .btn-auth {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: none;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
            background: linear-gradient(135deg, #2980b9, #3498db);
        }

        .btn-auth:active {
            transform: translateY(1px);
        }

        /* 圖標樣式 */
        .btn-auth i {
            font-size: 1.2rem;
            margin-right: 8px;
            transition: transform 0.3s ease;
        }

        .btn-auth:hover i {
            transform: translateX(3px);
        }

        /* 表單組件間距 */
        .form-floating:not(:last-child) {
            margin-bottom: 1.5rem;
        }

        /* 標籤切換按鈕優化 */
        .auth-tab {
            padding: 1rem 2rem;
            font-weight: 500;
            color: #7f8c8d;
            transition: all 0.3s ease;
            border-radius: 10px 10px 0 0;
        }

        .auth-tab.active {
            color: #3498db;
            background: rgba(52, 152, 219, 0.1);
        }

        /* 輸入框 focus 時的標籤顏色 */
        .form-floating input:focus ~ label {
            color: #3498db;
        }

        /* 表單標籤樣式 */
        .form-floating label {
            font-weight: 400;
            color: #6c757d;
            letter-spacing: 0.5px;
        }

        /* 輸入框文字樣式 */
        .form-floating input {
            font-weight: 400;
            letter-spacing: 0.5px;
            color: #2c3e50;
        }

        /* 按鈕文字樣式 */
        .btn-auth {
            font-family: 'Noto Sans TC', sans-serif;
            font-weight: 500;
            letter-spacing: 1px;
            font-size: 1.05rem;
        }

        /* 標籤切換文字樣式 */
        .auth-tab {
            font-weight: 500;
            letter-spacing: 0.8px;
            font-size: 1.05rem;
        }

        /* 返回首頁按鈕文字 */
        .back-to-home {
            font-weight: 500;
            letter-spacing: 0.8px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>

<body>
    <!-- 添加返回首頁鈕 -->
    <a href="../portal.php" class="back-to-home">
        <i class="bi bi-arrow-left"></i>
        <span>返回首頁</span>
    </a>

    <div class="auth-container">
        <div class="auth-image">
            <div class="auth-image-content">
                <h2>露營趣管理系統</h2>
                <p>歡迎使用露營趣管理系統，這裡提供完整的營地管理、訂單處理、會員管理等功能。</p>
                <div class="mt-4">
                    <i class="bi bi-tree" style="font-size: 3rem;"></i>
                </div>
            </div>
        </div>
        <div class="auth-content">
            <div class="auth-header">
                <h1>露營趣管理系統</h1>
                <p class="text-muted">歡迎使用管理系統</p>
            </div>

            <div class="auth-tabs">
                <div class="auth-tab active" data-form="login">登入</div>
                <div class="auth-tab" data-form="register">註冊</div>
            </div>

            <!-- 登入表單 -->
            <form method="POST" class="auth-form active" id="login-form" autocomplete="off">
                <input type="hidden" name="action" value="login">
                <div class="form-floating mb-3">
                    <input type="text"
                        class="form-control"
                        id="login-username"
                        name="username"
                        placeholder="帳號"
                        autocomplete="username"
                        required>
                    <label for="login-username">帳號</label>
                </div>

                <div class="form-floating mb-4">
                    <input type="password"
                        class="form-control"
                        id="login-password"
                        name="password"
                        placeholder="密碼"
                        autocomplete="current-password"
                        required>
                    <label for="login-password">密碼</label>
                </div>

                <button type="submit" class="btn btn-primary btn-auth">
                    <i class="bi bi-house-heart me-2"></i> 登入系統
                </button>
            </form>

            <!-- 註冊表單 -->
            <form method="POST" class="auth-form" id="register-form" autocomplete="off">
                <input type="hidden" name="action" value="register">
                <div class="form-floating mb-3">
                    <input type="text"
                        class="form-control"
                        id="register-username"
                        name="username"
                        placeholder="帳號"
                        autocomplete="username"
                        required>
                    <label for="register-username">帳號</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="email"
                        class="form-control"
                        id="register-email"
                        name="email"
                        placeholder="電子郵件"
                        autocomplete="email"
                        required>
                    <label for="register-email">電子郵件</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password"
                        class="form-control"
                        id="register-password"
                        name="password"
                        placeholder="密碼"
                        autocomplete="new-password"
                        required>
                    <label for="register-password">密碼</label>
                </div>

                <div class="form-floating mb-4">
                    <input type="password"
                        class="form-control"
                        id="register-confirm-password"
                        name="confirm_password"
                        placeholder="確認密碼"
                        autocomplete="new-password"
                        required>
                    <label for="register-confirm-password">確認密碼</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text"
                        class="form-control"
                        id="register-name"
                        name="name"
                        placeholder="姓名"
                        required>
                    <label for="register-name">
                        <i class="bi bi-tree me-2"></i>姓名
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-auth">
                    <i class="bi bi-tree me-2"></i>註冊帳號
                </button>
            </form>
        </div>
    </div>

    <script>
        // 添加粒子動畫
        function createParticles() {
            const container = document.createElement('div');
            container.className = 'particles';
            document.body.appendChild(container);

            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                container.appendChild(particle);
            }
        }

        // 表單切換動畫優化
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const currentForm = document.querySelector('.auth-form.active');
                const targetForm = document.getElementById(this.dataset.form + '-form');
                
                if (currentForm !== targetForm) {
                    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // 判斷滑動方向
                    const isMovingLeft = currentForm.id === 'register-form';
                    
                    // 添加相應的動畫類
                    currentForm.classList.add(isMovingLeft ? 'slide-left' : 'slide-right');
                    targetForm.classList.add(isMovingLeft ? 'slide-right' : 'slide-left');
                    
                    setTimeout(() => {
                        currentForm.classList.remove('active');
                        targetForm.classList.add('active');
                        
                        // 移除動畫類
                        currentForm.classList.remove('slide-left', 'slide-right');
                        targetForm.classList.remove('slide-left', 'slide-right');
                    }, 300);
                }
            });
        });

        // 初始化
        document.addEventListener('DOMContentLoaded', createParticles);

        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // 禁用提交按鈕
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>理中...';

            axios.post('', formData, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                const data = response.data;
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '登入成功',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '登入失敗',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '系統錯誤',
                    text: error.response?.data?.message || '請稍後再試'
                });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>登入系統';
            });
        });

        document.getElementById('register-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>處理中...';

            axios.post('', formData, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                const data = response.data;
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '註冊成功',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        this.reset();
                        document.querySelector('[data-form="login"]').click();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '註冊失敗',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '系統錯誤',
                    text: error.response?.data?.message || '請稍後再試'
                });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-person-plus me-2"></i>註冊帳號';
            });
        });
    </script>
</body>

</html>