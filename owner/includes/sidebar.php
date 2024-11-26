<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="/CampExplorer/owner/includes/style.css" rel="stylesheet">
<link href="/CampExplorer/owner/includes/pages-common.css" rel="stylesheet">
<style>
    /* 莫蘭迪藍色系變數 */
    :root {
        --morandi-blue-dark: #546E7A; /* 深莫蘭迪藍 */
        --morandi-blue: #78909C; /* 主要莫蘭迪藍 */
        --morandi-blue-light: #B0BEC5; /* 淺莫蘭迪藍 */
        --morandi-gray-blue: #CFD8DC; /* 莫蘭迪灰藍 */
        --morandi-light: #ECEFF1; /* 最淺莫蘭迪藍 */
        --transition-speed: 0.3s;
        --transition-timing: ease;
    }

    /* 側邊導覽基本設定 */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 250px;
        background-color: var(--morandi-blue-dark); /* 使用深莫蘭迪藍 */
        padding: 1.5rem;
        box-shadow: 2px 0 10px rgba(84, 110, 122, 0.15);
        z-index: 1000;
    }

    /* Logo 容器基礎樣式 */
    .logo-container {
        text-align: center;
        padding: 1.5rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    /* Logo 圖片容器 */
    .logo-image-container {
        position: relative;
        display: inline-block;
    }

    /* Logo 圖片基礎動畫 */
    .logo-image {
        max-width: 120px;
        height: auto;
        position: relative;
        z-index: 2;
        animation: logoFloat 4s ease-in-out infinite;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
    }

    /* 持續浮動动画 */
    @keyframes logoFloat {
        0%, 100% {
            transform: translateY(0) rotate(0);
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
        }
        25% {
            transform: translateY(-6px) rotate(2deg);
            filter: drop-shadow(0 8px 12px rgba(0, 0, 0, 0.3));
        }
        75% {
            transform: translateY(4px) rotate(-2deg);
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
        }
    }

    /* 光暈效果 */
    .logo-image-container::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 120%;
        height: 120%;
        background: radial-gradient(circle,
                rgba(255, 255, 255, 0.2) 0%,
                transparent 70%);
        transform: translate(-50%, -50%);
        animation: glowPulse 3s ease-in-out infinite;
        z-index: 1;
    }

    @keyframes glowPulse {
        0%, 100% {
            opacity: 0.3;
        }
        50% {
            opacity: 0.7;
        }
    }

    /* 自動旋轉的粒子 */
    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        pointer-events: none;
        animation: particleRotate 3s linear infinite;
    }

    @keyframes particleRotate {
        0% {
            transform: rotate(0deg) translateY(-40px);
            opacity: 0;
        }
        30%, 70% {
            opacity: 1;
        }
        100% {
            transform: rotate(360deg) translateY(-40px);
            opacity: 0;
        }
    }

    /* 為每個粒子設置不同的延遲和動畫時間 */
    .particle:nth-child(1) {
        animation: particleRotate 3s linear infinite;
        animation-delay: 0s;
    }
    .particle:nth-child(2) {
        animation: particleRotate 3.5s linear infinite;
        animation-delay: 0.7s;
    }
    .particle:nth-child(3) {
        animation: particleRotate 4s linear infinite;
        animation-delay: 1.4s;
    }
    .particle:nth-child(4) {
        animation: particleRotate 4.5s linear infinite;
        animation-delay: 2.1s;
    }
    .particle:nth-child(5) {
        animation: particleRotate 5s linear infinite;
        animation-delay: 2.8s;
    }

    /* 標題容器相關樣式 */
    .title-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.3rem;
        position: relative;
    }

    /* 中文標題樣式 */
    .logo-title {
        color: white;
        font-size: 1.4rem;
        font-weight: 500;
        margin: 0;
        line-height: 1.2;
        letter-spacing: 2px;
        position: relative;
        display: flex;
        justify-content: center;
        gap: 2px;
    }

    /* 個別字元樣式 */
    .logo-title span {
        display: inline-block;
        position: relative;
        transition: all var(--transition-speed) var(--transition-timing);
    }

    /* 字元高亮效果 */
    .logo-title span::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
        background: linear-gradient(180deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.1) 50%,
                rgba(255, 255, 255, 0) 100%);
        opacity: 0;
        transition: all var(--transition-speed) var(--transition-timing);
    }

    /* 英文副標題樣式 */
    .logo-subtitle {
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.9rem;
        font-family: 'Arial', sans-serif;
        text-transform: uppercase;
        font-weight: 300;
        position: relative;
        display: inline-block;
        transition: all var(--transition-speed) var(--transition-timing);
        text-shadow: 0 0 1px rgba(255, 255, 255, 0.3);
    }

    /* 滑鼠懸停效果 */
    .logo-container:hover .logo-title span {
        transform: translateY(-1px);
        text-shadow: 0 0 8px rgba(255, 255, 255, 0.3);
    }

    .logo-container:hover .logo-title span::after {
        opacity: 1;
    }

    /* 為每個字添加不同的动画延迟 */
    .logo-title span:nth-child(1) {
        transition-delay: 0.05s;
    }
    .logo-title span:nth-child(2) {
        transition-delay: 0.1s;
    }
    .logo-title span:nth-child(3) {
        transition-delay: 0.15s;
    }
    .logo-title span:nth-child(4) {
        transition-delay: 0.2s;
    }

    /* 分隔線光效 */
    .title-separator::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 30%;
        height: 100%;
        background: linear-gradient(90deg,
                transparent,
                rgba(255, 255, 255, 0.8),
                transparent);
        opacity: 0;
        transition: all var(--transition-speed) var(--transition-timing);
    }

    /* 滑鼠懸停效果 */
    .logo-container:hover .title-separator::after {
        left: 100%;
        opacity: 0.5;
    }

    /* 整體容器光暈效果 */
    .logo-container::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at center,
                rgba(255, 255, 255, 0.1) 0%,
                transparent 70%);
        opacity: 0;
        transition: opacity var(--transition-speed) var(--transition-timing);
    }

    .logo-container:hover::after {
        opacity: 1;
    }

    /* 分隔線效果 */
    .title-separator {
        width: 60%;
        height: 1px;
        background: linear-gradient(90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.5) 50%,
                transparent 100%);
        margin: 0.4rem 0;
        transition: all var(--transition-speed) var(--transition-timing);
    }

    /* 滑鼠懸停时分隔线效果 */
    .logo-container:hover .title-separator {
        width: 70%;
        background: linear-gradient(90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.8) 50%,
                transparent 100%);
    }

    /* 點擊效果 */
    .logo-container:active .logo-subtitle {
        transform: scale(0.95);
    }

    /* 閃光效果 */
    @keyframes shine {
        0% {
            background-position: -100% 50%;
        }
        100% {
            background-position: 200% 50%;
        }
    }

    .logo-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg,
                transparent,
                rgba(255, 255, 255, 0.1),
                transparent);
        background-size: 200% 100%;
        animation: shine 3s infinite;
        opacity: 0;
        transition: opacity var(--transition-speed) var(--transition-timing);
    }

    .logo-container:hover::before {
        opacity: 1;
    }

    /* 圖片點擊效果 */
    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(0.95);
        }
        100% {
            transform: scale(1);
        }
    }

    /* 圖片環繞粒子效果 */
    @keyframes particle {
        0% {
            transform: rotate(0deg) translateY(-40px);
            opacity: 0;
        }
        50% {
            opacity: 1;
        }
        100% {
            transform: rotate(360deg) translateY(-40px);
            opacity: 0;
        }
    }

    /* 粒子元素 */
    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        pointer-events: none;
        opacity: 0;
    }

    .logo-image-container:hover .particle {
        animation: particle 2s linear infinite;
    }

    /* 為每個粒子設置不同的延遲 */
    .particle:nth-child(1) {
        animation-delay: 0s;
    }
    .particle:nth-child(2) {
        animation-delay: 0.4s;
    }
    .particle:nth-child(3) {
        animation-delay: 0.8s;
    }
    .particle:nth-child(4) {
        animation-delay: 1.2s;
    }
    .particle:nth-child(5) {
        animation-delay: 1.6s;
    }

    /* 互動狀態類 */
    .logo-image-container.active .logo-image {
        animation: pulse 0.5s ease;
    }

    .logo-image-container.floating .logo-image {
        animation: float 3s ease-in-out infinite;
    }

    /* 標題动画效果 */
    .logo-title {
        color: white;
        font-size: 1.2rem;
        font-weight: 500;
        margin-top: 0.8rem;
        margin-bottom: 0.25rem;
        position: relative;
        display: inline-block;
        transition: all var(--transition-speed) var(--transition-timing);
    }

    .logo-container:hover .logo-title {
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* 副標題動畫 */
    .logo-subtitle {
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.9rem;
        opacity: 0.8;
        transition: all var(--transition-speed) var(--transition-timing);
        position: relative;
        display: inline-block;
    }

    .logo-subtitle::before,
    .logo-subtitle::after {
        content: '';
        position: absolute;
        width: 0;
        height: 1px;
        background-color: var(--morandi-blue-light);
        top: 50%;
        transition: all var(--transition-speed) var(--transition-timing);
        opacity: 0;
    }

    .logo-subtitle::before {
        left: -15px;
    }

    .logo-subtitle::after {
        right: -15px;
    }

    .logo-container:hover .logo-subtitle {
        opacity: 1;
        letter-spacing: 1px;
    }

    .logo-container:hover .logo-subtitle::before,
    .logo-container:hover .logo-subtitle::after {
        width: 10px;
        opacity: 1;
    }

    .logo-container:hover::before {
        opacity: 1;
    }

    /* 導覽基本樣 */
    .nav {
        margin-top: 1rem;
    }

    .nav-item {
        margin-bottom: 0.5rem;
    }

    /* 導覽連結樣式 */
    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.8rem 1.2rem;
        color: rgba(255, 255, 255, 0.9) !important;
        border-radius: 8px;
        transition: all var(--transition-speed) var(--transition-timing);
        position: relative;
        overflow: hidden;
    }

    /* 懸停效果 */
    .nav-link:hover {
        background-color: rgba(176, 190, 197, 0.15);
        transform: translateX(5px);
    }

    .nav-link:hover i {
        color: white;
        transform: scale(1.1);
    }

    /* 點擊效果 */
    .nav-link:active {
        transform: translateX(5px) scale(0.98);
    }

    /* 發光效果 */
    .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.1),
            transparent
        );
        transition: left 0.5s ease;
    }

    .nav-link:hover::before {
        left: 100%;
    }

    /* 圖標樣式 */
    .nav-link i:not(.submenu-icon) {
        width: 24px;
        font-size: 1.1rem;
        margin-right: 12px;
        text-align: center;
        color: var(--morandi-gray-blue);
    }

    /* 文字樣式 */
    .nav-text {
        flex: 1;
        font-size: 0.95rem;
        font-weight: 500;
    }

    /* 子選單項目樣式 */
    .submenu .nav-link {
        padding-left: 1rem;
        font-size: 0.95em;
    }

    /* 活動狀態樣式 */
    .nav-link.active {
        background-color: rgba(176, 190, 197, 0.2);
        color: white;
        box-shadow: 0 2px 8px rgba(84, 110, 122, 0.2);
    }

    .nav-link.active i {
        color: white;
    }

    /* 登出按鈕特殊樣式 */
    .nav-item:last-child {
        margin-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 1rem;
    }

    /* RWD 調整 */
    @media (max-width: 768px) {
        .nav-link {
            padding: 0.6rem 1rem;
        }

        .nav-text {
            font-size: 0.9rem;
        }

        .submenu {
            padding-left: 2.4rem;
        }
    }

    /* 滾動條美化 */
    .sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(176, 190, 197, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(176, 190, 197, 0.2);
        border-radius: 2px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(176, 190, 197, 0.3);
    }

    /* 移除 Bootstrap 默認的下拉箭頭 */
    .dropdown-toggle::after {
        display: none;
    }

    /* 自定義下拉箭頭樣式 */
    .submenu-icon {
        font-size: 0.8rem;
        margin-left: auto;
        transition: transform var(--transition-speed) var(--transition-timing);
    }

    /* 展開時箭頭旋轉 */
    .dropdown-toggle[aria-expanded="true"] .submenu-icon {
        transform: rotate(180deg);
    }

    /* 子選單圖標樣式 */
    .submenu .nav-link i {
        width: 24px;
        font-size: 1rem;
        margin-right: 12px;
        text-align: center;
        color: var(--morandi-gray-blue);
    }
</style>
<div class="sidebar">
    <a href="/CampExplorer/owner/index.php?page=dashboard"
        class="text-decoration-none nav-async-link"
        data-page="dashboard">
        <div class="logo-container">
            <div class="logo-image-container">
                <!-- 粒子元素 -->
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>

                <img src="/CampExplorer/assets/images/logo.png"
                    alt="Camp Explorer Logo"
                    class="logo-image">
            </div>
            <div class="title-container">
                <div class="logo-title">
                    <span>營</span><span>主</span><span>後</span><span>台</span>
                </div>
                <div class="title-separator"></div>
                <div class="logo-subtitle">Camp Explorer</div>
            </div>
        </div>
    </a>

    <nav class="nav flex-column mt-4">
        <!-- 數據中心 -->
        <div class="nav-item">
            <a href="/CampExplorer/owner/index.php?page=dashboard" 
               class="nav-link nav-async-link" 
               data-page="dashboard">
                <i class="bi bi-speedometer2"></i>
                <span class="nav-text">數據中心</span>
            </a>
        </div>

        <!-- 營地申請 -->
        <div class="nav-item">
            <a href="/CampExplorer/owner/index.php?page=camp_add" 
               class="nav-link nav-async-link" 
               data-page="camp_add">
                <i class="bi bi-house-door-fill"></i>
                <span class="nav-text">營地申請</span>
            </a>
        </div>

        <!-- 營地狀態 -->
        <div class="nav-item">
            <a href="/CampExplorer/owner/index.php?page=camp_status" 
               class="nav-link nav-async-link" 
               data-page="camp_status">
                <i class="bi bi-info-circle"></i>
                <span class="nav-text">營地狀態</span>
            </a>
        </div>

        <!-- 營位管理 -->
        <div class="nav-item">
            <a href="/CampExplorer/owner/index.php?page=spot_list" 
               class="nav-link nav-async-link" 
               data-page="spot_list">
                <i class="bi bi-grid"></i>
                <span class="nav-text">營位管理</span>
            </a>
        </div>

        <!-- 活動管 -->
        <div class="nav-item">
            <a href="/CampExplorer/owner/index.php?page=activity_list" 
               class="nav-link nav-async-link" 
               data-page="activity_list">
                <i class="bi bi-calendar-event"></i>
                <span class="nav-text">活動管理</span>
            </a>
        </div>

        <!-- 營位預訂管理 -->
        <div class="nav-item">
            <a href="/CampExplorer/owner/index.php?page=order_management" 
               class="nav-link nav-async-link" 
               data-page="order_management">
                <i class="bi bi-calculator"></i>
                <span class="nav-text">營位預訂管理</span>
            </a>
        </div>

        <!-- 登出系統 -->
        <div class="nav-item mt-auto">
            <a href="/CampExplorer/owner/logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right"></i>
                <span class="nav-text">登出系統</span>
            </a>
        </div>
    </nav>
</div>

<script>
    // Logo 動畫相關函數
    function initLogoAnimation(logoContainer) {
        logoContainer.classList.add('floating');
        
        logoContainer.addEventListener('click', function() {
            handleLogoClick(this);
        });
        
        logoContainer.addEventListener('mouseenter', function() {
            this.classList.remove('floating');
        });
        
        logoContainer.addEventListener('mouseleave', function() {
            this.classList.add('floating');
        });
    }

    function handleLogoClick(element) {
        element.classList.remove('floating');
        element.classList.add('active');
        
        setTimeout(() => {
            element.classList.remove('active');
            element.classList.add('floating');
        }, 500);
    }

    // 初始化 Logo 動畫
    document.addEventListener('DOMContentLoaded', function() {
        const logoContainer = document.querySelector('.logo-image-container');
        if (logoContainer) {
            initLogoAnimation(logoContainer);
        }
    });
</script>