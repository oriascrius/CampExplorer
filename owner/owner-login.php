<?php
session_start();
require_once __DIR__ . '/../camping_db.php';

// 使用 camping_db.php 中已建立的連接
$conn = $db;

// 如果已經登入，重定向到營主後台
if (isset($_SESSION['owner_id'])) {
    header("Location: index.php?page=dashboard");
    exit;
}

// AJAX 請求處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    try {
        // 處理註冊請求
        if (isset($_POST['action']) && $_POST['action'] === 'register') {
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $company_name = trim($_POST['company_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            // 基本驗證
            if (empty($email) || empty($password) || empty($name) || empty($company_name)) {
                throw new Exception('填欄位不能為空');
            }

            try {
                // 開始交易
                $conn->beginTransaction();

                // 檢查信箱是否已存在
                $stmt = $conn->prepare("SELECT COUNT(*) FROM owners WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('此信箱已被註冊');
                }

                // 新增營主資料 - 使用 MD5 加密密碼
                $stmt = $conn->prepare("INSERT INTO owners (email, password, name, company_name, phone, address, status) VALUES (?, ?, ?, ?, ?, ?, 1)");

                // 記錄 SQL 執行前的資料
                error_log("Attempting to insert owner with email: " . $email);

                $result = $stmt->execute([
                    $email,
                    md5($password), // 使用 MD5 加密密碼
                    $name,
                    $company_name,
                    $phone,
                    $address
                ]);


                if (!$result) {
                    error_log("Insert failed. Error info: " . json_encode($stmt->errorInfo()));
                    throw new Exception('註冊失敗，請稍後再試');
                }

                // 提交交易
                $conn->commit();
                error_log("Transaction committed successfully for email: " . $email);

                // 修改返回響應,添加 showLogin = true
                echo json_encode([
                    'success' => true,
                    'message' => '註冊成功！請使用新帳號登入',
                    'showLogin' => true  // 新增這行
                ]);
                return;
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Registration error: " . $e->getMessage());
                throw $e;
            }
        }

        // 處理登入請求
        if (isset($_POST['action']) && $_POST['action'] === 'login') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                throw new Exception('請輸入信箱和密碼');
            }

            // 檢查帳號狀態
            $stmt = $conn->prepare("SELECT * FROM owners WHERE email = ? AND status = 1");
            $stmt->execute([$email]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$owner) {
                error_log("Login failed: No user found with email: " . $email);
                throw new Exception('信箱或密碼錯誤');
            }

            // 使用 MD5 加密比對密碼
            if (md5($password) === $owner['password']) {
                // 設置 session
                $_SESSION['owner_id'] = $owner['id'];
                $_SESSION['owner_name'] = $owner['name'];
                $_SESSION['owner_email'] = $owner['email'];
                $_SESSION['owner_company'] = $owner['company_name'];

                echo json_encode([
                    'success' => true,
                    'message' => '登入成功！',
                    'redirect' => 'index.php'
                ]);
            } else {
                throw new Exception('信箱或密碼錯誤');
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 在頁面頂部生成 CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>營主專區 - 露營趣</title>
    <!-- 引入 Google Fonts - Noto Sans TC 和 Rubik -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* 更新字體設定 */
        :root {
            --font-primary: 'Rubik', 'Noto Sans TC', sans-serif;
            --font-secondary: 'Noto Sans TC', sans-serif;
        }

        body {
            font-family: var(--font-secondary);
        }

        .auth-title {
            font-family: var(--font-primary);
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .welcome-message p {
            font-family: var(--font-secondary);
            font-weight: 300;
            line-height: 1.8;
            color: var(--primary);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            animation: textFadeIn 0.8s ease-out;
            margin-bottom: 0.8rem;
        }

        .welcome-message p:first-child {
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 500;
            font-size: 1.2rem;
        }

        .sub-text {
            color: var(--primary-light);
            opacity: 0.9;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        @keyframes textFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 精緻化配色系統 */
        :root {
            --primary: #2B4865;
            --primary-light: #256D85;
            --accent: #8FE3CF;
            --accent-light: #A5F1E9;
            --hover: #7FBCD2;
            --gradient-1: linear-gradient(135deg, #2B4865, #256D85);
            --gradient-2: linear-gradient(135deg, #256D85, #8FE3CF);
            --gradient-hover: linear-gradient(135deg, #8FE3CF, #256D85);
        }

        /* 基礎設置 */
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-secondary);
        }

        /*  */
        .auth-container {
            width: 100%;
            max-width: 450px;
            margin: 2rem;
            position: relative;
        }

        /* 玻璃擬態效果卡片 */
        .auth-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        /* 動態光暈效果 */
        .auth-content::before,
        .auth-content::after {
            display: none;
        }

        /* 標題區域樣式優化 */
        .auth-header {
            position: relative;
            z-index: 1;
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-title {
            font-family: var(--font-primary);
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary);
            /* 移除漸層效果，改用純色加發光效果 */
            text-shadow: 
                0 0 10px rgba(143, 227, 207, 0.5),  /* 主要發光效果 */
                0 0 20px rgba(143, 227, 207, 0.3),  /* 次要發光效果 */
                0 0 30px rgba(143, 227, 207, 0.1);  /* 最外層發光 */
            /* 確保不會被其他動畫影響 */
            position: relative;
            transform: none !important;
            transition: none !important;
            /* 移除之前的背景漸層 */
            background: none;
            -webkit-background-clip: initial;
            -webkit-text-fill-color: initial;
        }

        /* 移除其他不必要的效果 */
        .auth-title::before,
        .auth-title::after {
            display: none;
        }

        /* 標籤切換區樣式簡化 */
        .auth-tabs {
            display: flex;
            gap: 0.5rem;
            background: #f5f5f5;
            padding: 0.3rem;
            border-radius: 8px;
            width: 80%;
            margin: 0 auto 1.5rem;
        }

        .auth-tab {
            flex: 1;
            padding: 0.4rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--primary);
            background: transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        /* 移除下滑線和hover效果，保持簡單的背景色變化 */
        .auth-tab:hover {
            background: rgba(43, 72, 101, 0.1);
        }

        .auth-tab.active {
            background: var(--primary);
            color: white;
        }

        /* 移除之前的下滑線相關樣式 */
        .auth-tab::after {
            display: none;
        }

        /* 表單樣式 */
        .form-floating {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-floating > .form-control {
            padding: 1rem 1rem 0.5rem 2.5rem;
            height: calc(3.5rem + 2px);
            border: 2px solid rgba(43, 72, 101, 0.1);
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.9);
        }

        .form-floating > .form-control:hover {
            border-color: var(--hover);
        }

        .form-floating > .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(143, 227, 207, 0.2);
            transform: translateY(-2px);
        }

        .form-floating > label {
            padding: 1rem 1rem 0.5rem 2.5rem;
            height: 100%;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out, transform .1s ease-in-out;
            color: var(--primary);
            opacity: 0.7;
        }

        .form-floating > label i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            transition: all 0.3s ease;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: 0.65;
            transform: scale(0.85) translateY(-0.5rem);
            padding-left: 2.5rem;
        }

        .form-floating > .form-control:focus ~ label i {
            color: var(--accent);
            transform: translateY(-50%) scale(1.1);
        }

        /* 按鈕樣式 */
        .btn-auth {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            background: var(--gradient-1);
            box-shadow: 0 4px 15px rgba(43, 72, 101, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-auth::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-hover);
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: -1;
        }

        .btn-auth:hover::before {
            transform: translateX(100%);
        }

        .btn-auth:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 10px 20px rgba(37, 109, 133, 0.2),
                0 6px 6px rgba(37, 109, 133, 0.1);
        }

        /* 表單切換動畫優化 */
        .forms-container {
            position: relative;
            min-height: 300px;
        }

        .auth-form {
            width: 100%;
            opacity: 0;
            visibility: hidden;
            position: absolute;
            transform: translateX(50px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: grid;
            gap: 1rem;
        }

        .auth-form.active {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
            position: relative;
        }

        /* 標籤切換效果增強 */
        .auth-tab {
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .auth-tab::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(-50%);
        }

        .auth-tab:hover::after {
            width: 100%;
        }

        .auth-tab.active::after {
            width: 100%;
            background: var(--accent-light);
        }

        /* 表單切換動畫增強 */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(30px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
            to {
                opacity: 0;
                transform: translateX(-30px) scale(0.9);
            }
        }

        .auth-form {
            animation: slideOut 0.3s ease forwards;
        }

        .auth-form.active {
            animation: slideIn 0.3s ease forwards;
        }

        /* 輸入框聚焦特效 */
        .form-floating > label {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-floating > .form-control:focus ~ label {
            color: var(--accent);
            transform: scale(0.85) translateY(-0.75rem) translateX(0.15rem);
        }


        .auth-title {
            animation: float 6s ease-in-out infinite;
        }

        /* 返回按鈕動畫優化 */
        .back-to-home {
            position: fixed;
            top: 2rem;
            left: 2rem;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .back-to-home:hover {
            background: var(--gradient-hover);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 5px 15px rgba(143, 227, 207, 0.3);
        }

        /* 錯誤提示動畫 */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .form-control.is-invalid {
            animation: shake 0.5s ease-in-out;
        }

        /* 波浪容器 */
        .waves-container {
            position: absolute;
            width: 100%;
            height: 100%;
            bottom: 0;
            left: 0;
        }

        /* 波浪效果 */
        .wave {
            position: absolute;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 40%;
            transform-origin: 50% 48%;
            animation: wave-rotate 12s linear infinite;
        }

        .wave:nth-child(2) {
            background: rgba(143, 227, 207, 0.05);
            animation-duration: 16s;
            animation-direction: reverse;
        }

        .wave:nth-child(3) {
            background: rgba(37, 109, 133, 0.05);
            animation-duration: 20s;
        }

        /* 漂浮氣泡效果 */
        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: none;
            opacity: 0.5;
        }



        /* 漸層光暈效果 */
        .gradient-orb {
            position: absolute;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, 
                rgba(143, 227, 207, 0.2) 0%,
                rgba(37, 109, 133, 0.1) 50%,
                transparent 70%
            );
            border-radius: 50%;
            filter: blur(20px);
            animation: orb-float 8s ease-in-out infinite;
        }

        @keyframes orb-float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -30px); }
        }

        /* 密碼欄位容器 */
        .password-field {
            position: relative;
            width: 100%;
        }

        /* 密碼切換按鈕 */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            padding: 8px;
            color: rgba(43, 72, 101, 0.6);
            cursor: pointer;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            border-radius: 50%;
        }

        /* 懸停效果 */
        .password-toggle:hover {
            color: var(--primary);
            background-color: rgba(143, 227, 207, 0.1);
        }

        /* 點擊效果 */
        .password-toggle:active {
            transform: translateY(-50%) scale(0.95);
        }

        /* 圖標樣式 */
        .password-toggle i {
            font-size: 1.1rem;
            transition: all 0.2s ease;
        }

        /* 密碼輸入框右側留空間給按鈕 */
        .password-field .form-control {
            padding-right: 45px !important;
        }

        /* 聚焦時的疊層效果 */
        .password-field .form-control:focus ~ .password-toggle {
            color: var(--primary);
        }

        /* 禁用時的樣式 */
        .password-field .form-control:disabled ~ .password-toggle {
            opacity: 0.5;
            pointer-events: none;
        }

        /* 錯誤狀態 */
        .password-field.is-invalid .password-toggle {
            color: #dc3545;
        }

        /* 成功狀態 */
        .password-field.is-valid .password-toggle {
            color: #198754;
        }

        .password-toggle-icon {
            transition: transform 0.2s ease;
        }
        
        .password-toggle-icon.rotate {
            transform: rotate(180deg);
        }
        
        .password-toggle:focus {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }
        
        .password-toggle:focus:not(:focus-visible) {
            outline: none;
        }
        
        @media (prefers-reduced-motion: reduce) {
            .password-toggle-icon {
                transition: none;
            }
        }

        @keyframes float {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            50% { opacity: 0.8; }
            100% { transform: translateY(-20vh) scale(1); opacity: 0; }
        }

        /* 輸入框聚焦效果 */
        .input-focused .form-control {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(143, 227, 207, 0.2);
        }

        /* 按鈕懸浮效果 */
        .btn-auth {
            position: relative;
            overflow: hidden;
        }

        .btn-auth::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
            transform: scale(0);
            transition: transform 0.6s;
        }

        .btn-auth:hover::after {
            transform: scale(1);
        }

        /* 露營背景容器 */
        .camping-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #1a2a3a 0%, #2B4865 100%);
            overflow: hidden;
        }

        /* 星空效果優化 */
        .stars {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .star {
            position: absolute;
            background: #fff;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        /* 山脈效果優化 */
        .mountains {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 40%;
            background: linear-gradient(135deg, #256D85 0%, #2B4865 100%);
            clip-path: polygon(0 100%, 15% 55%, 35% 80%, 50% 60%, 65% 80%, 85% 45%, 100% 100%);
        }

        /* 樹木效果優化 */
        .trees {
            position: absolute;
            bottom: 20%;
            width: 100%;
            height: 30%;
            display: flex;
            justify-content: space-around;
        }

        .tree {
            width: 30px;
            height: 60px;
            background: #1a2a3a;
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            animation: sway 4s ease-in-out infinite;
            opacity: 0.8;
        }

        /* 帳篷效果優化 */
        .tent {
            position: absolute;
            bottom: 22%;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 90px;
        }

        .tent-body {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 100%;
            background: #8FE3CF;
            clip-path: polygon(0 100%, 50% 0, 100% 100%);
            animation: float 6s ease-in-out infinite;
        }

        /* 營火效果���化 */
        .campfire {
            position: absolute;
            bottom: 21%;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
        }

        .flame {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            background: #ff6b6b;
            border-radius: 50% 50% 0 50%;
            animation: flicker 1s ease-in-out infinite;
        }

        /* 動畫效果優化 */
        @keyframes twinkle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(0.8); }
        }

        @keyframes sway {
            0%, 100% { transform: rotate(-3deg) translateY(0); }
            50% { transform: rotate(3deg) translateY(-5px); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes flicker {
            0%, 100% { transform: scale(1) rotate(45deg); opacity: 0.8; }
            50% { transform: scale(1.1) rotate(45deg); opacity: 1; }
        }
    </style>
</head>

<body>
    <!-- 返回首頁按鈕 -->
    <a href="../portal.php" class="back-to-home">
        <i class="bi bi-arrow-left me-2"></i>返回首頁
    </a>

    <!-- 主要內容區 -->
    <div class="auth-container">
        <div class="auth-content">
            <div class="auth-header">
                <div class="text-center mb-4">
                    <h2 class="auth-title">營主專區</h2>
                    <div class="welcome-message">
                        <p class="main-message">加入露營趣大家庭，一同打造最棒的露營體驗！</p>
                        <p class="sub-text">專業營地管理系統，助您輕鬆經營</p>
                    </div>
                </div>

                <!-- 登入/註冊標籤 -->
                <div class="auth-tabs">
                    <button type="button" class="auth-tab active" data-form="login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>登入</span>
                    </button>
                    <button type="button" class="auth-tab" data-form="register">
                        <i class="bi bi-person-plus"></i>
                        <span>註冊</span>
                    </button>
                </div>
            </div>

            <!-- 表單容器 -->
            <div class="forms-container">
                <!-- 登入表單 -->
                <form method="POST" class="auth-form active" id="login-form">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="login-email" name="email" required>
                        <label for="login-email">
                            <i class="bi bi-envelope me-2"></i>電子信箱
                        </label>
                    </div>

                    <div class="form-floating password-field">
                        <input type="password" 
                               class="form-control" 
                               id="login-password" 
                               name="password" 
                               required
                               autocomplete="current-password"
                               aria-describedby="password-toggle-help">
                        <label for="login-password">
                            <i class="bi bi-lock me-2"></i>密碼
                        </label>
                        <button type="button" 
                                class="password-toggle" 
                                aria-label="切換密碼顯示"
                                data-bs-toggle="tooltip"
                                data-bs-placement="left"
                                title="點擊切換密碼顯示">
                            <i class="bi bi-eye password-toggle-icon" aria-hidden="true"></i>
                        </button>
                        <span id="password-toggle-help" class="visually-hidden">
                            點擊右側眼睛圖示可切換密碼顯示
                        </span>
                    </div>

                    <button type="submit" class="btn-auth">
                        <span>登入系統</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <!-- 註冊表單 -->
                <form method="POST" class="auth-form" id="register-form">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <!-- 重要資訊 - 跨欄 -->
                    <div class="form-floating">
                        <input type="email" class="form-control" id="register-email" name="email" required>
                        <label for="register-email">
                            <i class="bi bi-envelope me-2"></i>電子信箱
                        </label>
                    </div>

                    <div class="form-floating password-field">
                        <input type="password" class="form-control" id="register-password" name="password" required>
                        <label for="register-password">
                            <i class="bi bi-lock me-2"></i>密碼
                        </label>
                        <button type="button" class="password-toggle" aria-label="切換密碼顯示">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <!-- 基本資料 - 兩欄式 -->
                    <div class="form-floating">
                        <input type="text" class="form-control" id="register-name" name="name" required>
                        <label for="register-name">
                            <i class="bi bi-person me-2"></i>營主姓名
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="text" class="form-control" id="register-company" name="company_name" required>
                        <label for="register-company">
                            <i class="bi bi-building me-2"></i>公司名稱
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="tel" class="form-control" id="register-phone" name="phone">
                        <label for="register-phone">
                            <i class="bi bi-phone me-2"></i>聯絡電話
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="text" class="form-control" id="register-address" name="address">
                        <label for="register-address">
                            <i class="bi bi-geo-alt me-2"></i>地址
                        </label>
                    </div>

                    <button type="submit" class="btn-auth">
                        <span>註冊帳號</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 引入必要的 JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化所有功能
            initTabSwitching();
            initPasswordToggles();
            initFormSubmission();
            createCampingBackground();
        });

        // 表單切換功能
        function initTabSwitching() {
            const tabs = document.querySelectorAll('.auth-tab');
            const forms = document.querySelectorAll('.auth-form');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const targetForm = tab.dataset.form;
                    
                    // 切換標籤樣式
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    // 切換表單顯示，添加動畫效果
                    forms.forEach(form => {
                        if (form.id === `${targetForm}-form`) {
                            form.style.display = 'block';
                            setTimeout(() => {
                                form.classList.add('active');
                            }, 50);
                        } else {
                            form.classList.remove('active');
                            setTimeout(() => {
                                form.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });
        }

        // 密碼顯示切換
        function initPasswordToggles() {
            const toggles = document.querySelectorAll('.password-toggle');
            
            toggles.forEach(toggle => {
                // 初始化 tooltip
                new bootstrap.Tooltip(toggle);
                
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const input = this.closest('.password-field').querySelector('input');
                    const icon = this.querySelector('.password-toggle-icon');
                    
                    // 切換密碼顯示
                    const isVisible = input.type === 'text';
                    input.type = isVisible ? 'password' : 'text';
                    
                    // 更新圖標
                    icon.classList.toggle('bi-eye', isVisible);
                    icon.classList.toggle('bi-eye-slash', !isVisible);
                    
                    // 添加旋轉動畫
                    icon.classList.add('rotate');
                    setTimeout(() => icon.classList.remove('rotate'), 300);
                    
                    // 更新 aria-label 和 title
                    const newText = isVisible ? '顯示密碼' : '隱藏密碼';
                    this.setAttribute('aria-label', newText);
                    this.setAttribute('title', newText);
                    
                    // 銷毀並重新創建 tooltip
                    bootstrap.Tooltip.getInstance(this)?.dispose();
                    new bootstrap.Tooltip(this);
                });
                
                // 支援鍵盤操作
                toggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
            
            // 在表單提交時確保密碼是隱藏的
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', () => {
                    form.querySelectorAll('input[type="text"]').forEach(input => {
                        if (input.classList.contains('form-control')) {
                            input.type = 'password';
                        }
                    });
                });
            });
        }

        // 表單提交處理
        function initFormSubmission() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    // 添加載入動畫
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalContent = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>處理中...';
                    submitBtn.disabled = true;
                    
                    try {
                        const formData = new FormData(this);
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '成功！',
                                text: result.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                if (result.redirect) {
                                    window.location.href = result.redirect;
                                } else if (result.showLogin) {
                                    // 如果是註冊成功,切換到登入表單
                                    document.querySelector('.auth-tab[data-form="login"]').click();
                                    // 清空註冊表單
                                    form.reset();
                                }
                            });
                        } else {
                            throw new Error(result.message);
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: '錯誤',
                            text: error.message
                        });
                    } finally {
                        // 恢復按鈕狀態
                        submitBtn.innerHTML = originalContent;
                        submitBtn.disabled = false;
                    }
                });
            });
        }

        // 創建露營背景
        function createCampingBackground() {
            const container = document.querySelector('.auth-container');
            
            // 添加背景元素
            const backgroundElements = `
                <div class="camping-background">
                    <div class="stars"></div>
                    <div class="mountains"></div>
                    <div class="trees">
                        ${Array(5).fill('<div class="tree"></div>').join('')}
                    </div>
                    <div class="tent">
                        <div class="tent-body"></div>
                    </div>
                    <div class="campfire">
                        <div class="flame"></div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('afterbegin', backgroundElements);
            
            // 創建星星
            const starsContainer = document.querySelector('.stars');
            for (let i = 0; i < 50; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = `${Math.random() * 100}%`;
                star.style.top = `${Math.random() * 100}%`;
                star.style.width = `${Math.random() * 3}px`;
                star.style.height = star.style.width;
                star.style.animationDelay = `${Math.random() * 3}s`;
                starsContainer.appendChild(star);
            }
        }
    </script>

</body>

</html>