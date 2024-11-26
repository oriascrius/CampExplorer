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
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        scroll-behavior: smooth;
    }

    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.2);
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
        background: rgba(0, 0, 0, 0.1);
        border-left: 2px solid rgba(52, 152, 219, 0.5);
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
        display: block;
        margin-top: 0.5rem;
    }

    .logo-text:hover {
        text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
        transform: scale(1.1);
    }

    .logo-text:after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: 0;
        left: 50%;
        background-color: #fff;
        transition: all 0.3s ease;
    }

    .logo-text:hover:after {
        width: 100%;
        left: 0;
    }
</style>

<div class="px-0 sidebar">
    <div class="text-center mb-3">
        <a href="/CampExplorer/admin/index.php?page=dashboard" style="text-decoration: none;">
            <img src="/CampExplorer/assets/images/logo.png"
                alt="露營趣 LOGO" 
                class="img-fluid logo-animation"
                style="max-width: 120px;">
            <h4 class="text-white logo-text mt-2">露營趣後台</h4>
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

        <!-- 類別管理 -->
        <div class="nav-item">
            <div class="nav-link menu-toggle" data-bs-toggle="collapse" data-bs-target="#categoryMenu">
                <div><i class="bi bi-tags me-2"></i>類別管理</div>
                <i class="bi bi-chevron-down toggle-icon"></i>
            </div>
            <div class="sub-menu" id="categoryMenu">
                <a href="/CampExplorer/admin/index.php?page=spot_category" class="nav-link">營位類別管理</a>
                <a href="/CampExplorer/admin/index.php?page=product_category" class="nav-link">商品類別管理</a>
            </div>
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

        <!-- 訂單管理 -->
        <div class="nav-item">
            <div class="nav-link menu-toggle" data-bs-toggle="collapse" data-bs-target="#orderMenu">
                <div><i class="bi bi-receipt me-2"></i>訂單管理</div>
                <i class="bi bi-chevron-down toggle-icon"></i>
            </div>
            <div class="sub-menu" id="orderMenu">
                <a href="/CampExplorer/admin/index.php?page=orders_list" class="nav-link">商品訂單管理</a>
                <a href="/CampExplorer/admin/index.php?page=coupons_list" class="nav-link">營位訂單管理</a>
            </div>
        </div>

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
            this.initPageNavigation();
            
            const currentPath = window.location.pathname + window.location.search;
            if (currentPath) {
                this.openCurrentPageMenu(currentPath);
            }
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
                    e.preventDefault();
                    const subMenu = toggle.nextElementSibling;
                    const isCurrentlyActive = toggle.classList.contains('active');
                    const currentToggleIcon = toggle.querySelector('.toggle-icon');

                    // 如果点击的是当前已激活的菜单，则只关闭当前菜单
                    if (isCurrentlyActive) {
                        toggle.classList.remove('active');
                        subMenu.classList.remove('show');
                        currentToggleIcon.style.transform = 'rotate(0deg)';
                        return;
                    }

                    // 关闭所有子菜单和重置所有图标
                    document.querySelectorAll('.sub-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                        const menuToggle = menu.previousElementSibling;
                        menuToggle.classList.remove('active');
                        const toggleIcon = menuToggle.querySelector('.toggle-icon');
                        toggleIcon.style.transform = 'rotate(0deg)';
                    });

                    // 打开当前点击的菜单
                    toggle.classList.add('active');
                    subMenu.classList.add('show');
                    currentToggleIcon.style.transform = 'rotate(180deg)';
                });
            });
        },

        async loadPage(url) {
            try {
                const loadingDelay = setTimeout(() => {
                    Swal.fire({
                        title: '載入中...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }, 300);

                const response = await axios.get(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                clearTimeout(loadingDelay);

                if (typeof response.data === 'string') {
                    const container = document.querySelector('.main-content .container-fluid');
                    container.innerHTML = response.data;
                    
                    // 觸發自定義事件
                    const event = new CustomEvent('pageLoaded');
                    document.dispatchEvent(event);
                    
                    history.pushState({}, '', url);
                    this.highlightCurrentPage();
                } else {
                    throw new Error('無效的回應格式');
                }
            } catch (error) {
                console.error('頁面載入錯誤:', error);
                Swal.fire({
                    icon: 'error',
                    title: '錯誤',
                    text: '載入頁面失敗',
                    timer: 2000
                });
            } finally {
                Swal.close();
            }
        },

        initPageNavigation() {
            document.querySelectorAll('.sub-menu .nav-link').forEach(link => {
                link.addEventListener('click', async (e) => {
                    e.preventDefault();
                    await this.loadPage(link.href);
                });
            });

            window.addEventListener('popstate', async () => {
                await this.loadPage(window.location.href);
            });
        },

        highlightCurrentPage() {
            const currentPath = window.location.pathname + window.location.search;
            
            this.closeAllMenus();
            
            document.querySelectorAll('.nav-link').forEach(link => {
                const href = link.getAttribute('href');
                if (href && href.includes(currentPath)) {
                    link.classList.add('active');
                    const parentMenu = link.closest('.sub-menu');
                    if (parentMenu) {
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
                } else {
                    link.classList.remove('active');
                }
            });
        },

        openCurrentPageMenu(currentPath) {
            document.querySelectorAll('.nav-link').forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href)) {
                    const parentMenu = link.closest('.sub-menu');
                    if (parentMenu) {
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
                    link.classList.add('active');
                }
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        AdminUI.init();
    });
</script>