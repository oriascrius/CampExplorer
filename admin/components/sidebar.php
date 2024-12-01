<style>
    .sidebar {
        background: linear-gradient(180deg, #1a2236 0%, #2c3e50 100%);
        color: #ecf0f1;
        min-height: 100vh;
        width: 250px;
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        padding: 1rem 0;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        scroll-behavior: smooth;
    }

    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .nav-link {
        color: #a8b2c1 !important;
        padding: 0.8rem 1rem;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.1);
    }

    .nav-link.active {
        color: #fff !important;
        background: rgba(52, 152, 219, 0.2);
    }

    .sub-menu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        padding-left: 2rem;
    }

    .sub-menu.show {
        max-height: 500px;
    }

    .sub-menu .nav-link {
        opacity: 0;
        transform: translateX(-10px);
        transition: all 0.3s ease;
    }

    .sub-menu.show .nav-link {
        opacity: 1;
        transform: translateX(0);
    }

    /* 為每個子項目添加延遲動畫 */
    .sub-menu.show .nav-link:nth-child(1) {
        transition-delay: 0.1s;
    }

    .sub-menu.show .nav-link:nth-child(2) {
        transition-delay: 0.2s;
    }

    .sub-menu.show .nav-link:nth-child(3) {
        transition-delay: 0.3s;
    }

    .menu-toggle {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .toggle-icon {
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .menu-toggle.active .toggle-icon {
        transform: rotate(180deg);
    }

    .logo-animation {
        animation: float 3s ease-in-out infinite;
        transition: transform 0.3s ease;
        margin-bottom: 0.5rem;
    }

    .logo-animation:hover {
        transform: scale(1.2) rotate(5deg);
        filter: brightness(1.2);
    }

    @keyframes float {
        0% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }

        100% {
            transform: translateY(0px);
        }
    }

    .logo-text {
        transition: all 0.3s ease;
        position: relative;
        display: inline-block;
        margin: 0;
        padding: 0.2rem 0;
    }

    a {
        text-decoration: none;
    }

    .logo-text:hover {
        text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
    }

    .logo-text:after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -4px;
        left: 50%;
        background-color: #fff;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }

    .logo-text:hover:after {
        width: 110%;
    }

    .logo-container {
        padding: 1.5rem 0;
    }

    .logo-link {
        text-decoration: none;
        display: inline-block;
    }

    .logo-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .logo-animation {
        transition: transform 0.3s ease;
    }

    .logo-animation:hover {
        transform: scale(1.05);
    }

    .logo-text {
        position: relative;
        pointer-events: auto;
    }

    .logo-text:after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -4px;
        left: 50%;
        background-color: #fff;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }

    .logo-text:hover:after {
        width: 110%;
    }
    /* ***********共用*********** */
    h1{
        position: relative;
        font-weight: 700;
        color: #767676;
    }
    .btn-warning{
        color: #8b6a09;
        background-color: #ffc1076e;
        border: 0;
    }
    .bg-danger{
        background-color: #f5000029!important;
        color: #db0000 !important;
    }
    .bg-success{
        background-color: #0080003b!important;
        color: green !important;
    }
    .container.py-4{
        padding: 4rem;
        max-width: 100%;
    }
    .card{
        border-radius: 0px;
    }
    .card-header{
        background: #fff;
        border-radius: 30px 30px 0 0;
        border: 0;
        box-shadow: 0px 18px 10px rgba(0, 0, 0, 0.1);
    }
    
    tr th{
        border: 0;
    }
    tbody td{
        border: 0;
        padding: 20px 0 !important;
        color: #767676 !important;
    }
    .table thead{
        background-image: linear-gradient(to top, #0ba360 0%, #3cba92 100%);
    }
    .card-body{
        padding: 0 15px;
    }
    .card.border-0{
        background: #fff;
        box-shadow: 0px 7px 10px rgba(0, 0, 0, 0.1);
    }
    .btn-primary{
        background-color: #ecba82;
        border: 0;
    }
    .page-item.active .page-link{
        background-color: #ecba82;
        border: 1px solid #ecba82;
    }
    .page-item .page-link{
        color: #ecba82;
    }
    body{
        background-color: #f8f9fa;
    }
    .px-0.sidebar{
        margin: 25px;
        min-height: auto;
        background: #fefefe;
        padding: 0;
        border-radius: 30px;
    }
    .px-0.sidebar {
        overflow: hidden;
        .text-center.mb-3{
            background-image: linear-gradient(to top, #0ba360 0%, #3cba92 100%);
            padding-bottom: 20px;
            padding-top: 20px;
        }
        .nav-link{
            margin: 4px 0;
            color: #767676!important;
        }
        .nav-link.active{
            background: #f8f9fa;
            border-radius: 50px 0 0 50px;
            color: #fff !important;
            background-image: linear-gradient(to top, #0ba360 0%, #3cba92 100%);
        }
        .nav-link.active:hover{
            color: #fff !important;
        }
        .nav-link:hover{
            color: #a8b2c1 !important;
        }
        .nav.flex-column{
            padding-left: 15px;
        }
    }
    .container.py-4{
        padding: 4rem;
        max-width: 100%;
    }
</style>

<div class="px-0 sidebar">
    <div class="logo-container text-center mb-3">
        <a href="/CampExplorer/admin/index.php?page=dashboard" class="logo-link">
            <div class="logo-wrapper">
                <img src="/CampExplorer/assets/images/logo.png"
                    alt="露營趣 LOGO"
                    class="img-fluid logo-animation"
                    style="max-width: 120px;">
                <h4 class="text-white logo-text">露營趣後台</h4>
            </div>
        </a>
    </div>
    <nav class="nav flex-column">
        <!-- 審核管理 -->
        <div class="nav-item">
            <div class="nav-link menu-toggle" data-bs-toggle="collapse" data-bs-target="#reviewMenu">
                <div><i class="bi bi-check-circle me-2"></i>審核管理</div>
                <i class="bi bi-chevron-down toggle-icon"></i>
            </div>
            <div class="sub-menu" id="reviewMenu">
                <a href="/CampExplorer/admin/index.php?page=camps_review" class="nav-link">營地審核</a>
                <a href="/CampExplorer/admin/index.php?page=spots_review" class="nav-link">營位審核</a>
            </div>
        </div>

        <!-- 商品類別管理 -->
        <div class="nav-item">
            <a href="/CampExplorer/admin/index.php?page=product_category" class="nav-link">
                <i class="bi bi-tags me-2"></i>商品類別管理
            </a>
        </div>

        <!-- 營區管理 -->
        <div class="nav-item">
            <div class="nav-link menu-toggle" data-bs-toggle="collapse" data-bs-target="#campMenu">
                <div><i class="bi bi-tree me-2"></i>營區管理</div>
                <i class="bi bi-chevron-down toggle-icon"></i>
            </div>
            <div class="sub-menu" id="campMenu">
                <a href="/CampExplorer/admin/index.php?page=approved_camps" class="nav-link">全部營地列表</a>
                <a href="/CampExplorer/admin/index.php?page=approved_spots" class="nav-link">全部營位列表</a>
            </div>
        </div>

        <!-- 商品管理 -->
        <a href="/CampExplorer/admin/index.php?page=products_list" class="nav-link">
            <i class="bi bi-box me-2"></i>商品管理
        </a>

        <!-- 商品訂單管理 -->
        <a href="/CampExplorer/admin/index.php?page=orders_list" class="nav-link">
            <i class="bi bi-receipt me-2"></i>商品訂單管理
        </a>

        <!-- 使用者管理 -->
        <div class="nav-item">
            <div class="nav-link menu-toggle" data-bs-toggle="collapse" data-bs-target="#userMenu">
                <div><i class="bi bi-people me-2"></i>使用者管理</div>
                <i class="bi bi-chevron-down toggle-icon"></i>
            </div>
            <div class="sub-menu" id="userMenu">
                <a href="/CampExplorer/admin/index.php?page=members_list" class="nav-link">會員管理</a>
                <a href="/CampExplorer/admin/index.php?page=owners_list" class="nav-link">營主管理</a>
            </div>
        </div>

        <!-- 優惠券管理 -->
        <a href="/CampExplorer/admin/index.php?page=coupons_list" class="nav-link">
            <i class="bi bi-ticket-perforated me-2"></i>優惠券管理
        </a>

        <!-- 官方文章管理 -->
        <a href="/CampExplorer/admin/index.php?page=articles_list" class="nav-link">
            <i class="bi bi-file-text me-2"></i>官方文章管理
        </a>

        <!-- 登出按鈕 -->
        <a href="/CampExplorer/admin/logout.php" class="nav-link text-danger mt-3">
            <i class="bi bi-box-arrow-right me-2"></i>登出系統
        </a>
    </nav>
</div>

<script>
    const AdminUI = {
        init() {
            this.closeAllMenus();
            this.initMenuHandlers();
            this.highlightCurrentPage();
        },

        closeAllMenus() {
            document.querySelectorAll('.sub-menu').forEach(menu => {
                menu.classList.remove('show');
                const menuToggle = menu.previousElementSibling;
                if (menuToggle) {
                    menuToggle.classList.remove('active');
                    const toggleIcon = menuToggle.querySelector('.toggle-icon');
                    if (toggleIcon) {
                        toggleIcon.style.transform = 'rotate(0deg)';
                    }
                }
            });
        },

        initMenuHandlers() {
            document.querySelectorAll('.menu-toggle').forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    const subMenu = toggle.nextElementSibling;
                    const isCurrentlyActive = toggle.classList.contains('active');
                    const currentToggleIcon = toggle.querySelector('.toggle-icon');

                    if (isCurrentlyActive) {
                        toggle.classList.remove('active');
                        subMenu.classList.remove('show');
                        currentToggleIcon.style.transform = 'rotate(0deg)';
                        return;
                    }

                    this.closeAllMenus();
                    toggle.classList.add('active');
                    subMenu.classList.add('show');
                    currentToggleIcon.style.transform = 'rotate(180deg)';
                });
            });
        },

        highlightCurrentPage() {
            const currentPath = window.location.pathname + window.location.search;
            
            // 先移除所有active狀態
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // 找到當前頁面對應的連結並設置active
            document.querySelectorAll('.nav-link').forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href)) {
                    link.classList.add('active');
                    
                    // 如果active的連結在子選單中，只打開該子選單
                    const parentMenu = link.closest('.sub-menu');
                    if (parentMenu) {
                        // 先關閉所有子選單
                        document.querySelectorAll('.sub-menu').forEach(menu => {
                            menu.classList.remove('show');
                            const toggle = menu.previousElementSibling;
                            if (toggle) {
                                toggle.classList.remove('active');
                                const icon = toggle.querySelector('.toggle-icon');
                                if (icon) {
                                    icon.style.transform = 'rotate(0deg)';
                                }
                            }
                        });
                        
                        // 只打開當前連結所在的子選單
                        parentMenu.classList.add('show');
                        const menuToggle = parentMenu.previousElementSibling;
                        if (menuToggle) {
                            menuToggle.classList.add('active');
                            const toggleIcon = menuToggle.querySelector('.toggle-icon');
                            if (toggleIcon) {
                                toggleIcon.style.transform = 'rotate(180deg)';
                            }
                        }
                    }
                }
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        AdminUI.init();
    });
</script>