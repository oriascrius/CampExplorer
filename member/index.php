<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>露營趣 | 會員登入</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            font-family: 'Segoe UI', 'Microsoft JhengHei', sans-serif;
        }

        @keyframes gradient {
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

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header img {
            width: 120px;
            margin-bottom: 20px;
        }

        .auth-header h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .auth-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
        }

        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 15px;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .auth-tab.active {
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ddd;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
            border-color: #2c3e50;
        }

        .btn-auth {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background: #2c3e50;
            color: white;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .btn-auth:hover {
            background: #34495e;
            transform: translateY(-2px);
        }

        .social-login {
            text-align: center;
            margin-top: 20px;
        }

        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: white;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            transform: translateY(-3px);
        }

        .btn-facebook {
            background: #3b5998;
        }

        .btn-google {
            background: #db4437;
        }

        .btn-line {
            background: #00c300;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #ddd;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .form-check {
            margin-bottom: 20px;
        }

        @media (max-width: 576px) {
            .auth-card {
                padding: 20px;
            }
        }

        .btn-light {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: none;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-light:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
        }

        /* 返回首頁按鈕文字 */
        .back-to-home {
            font-weight: 500;
            letter-spacing: 0.8px;
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
    </style>
</head>

<body>
    <!-- 添加返回首頁鈕 -->
    <a href="../portal.php" class="back-to-home ">
        <i class="bi bi-arrow-left"></i>
        <span>返回首頁</span>
    </a>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>露營趣</h1>
                <p>歡迎回來，請登入您的帳號</p>
            </div>

            <div class="auth-tabs">
                <div class="auth-tab active" onclick="switchTab('login')">登入</div>
                <div class="auth-tab" onclick="switchTab('register')">註冊</div>
            </div>

            <!-- 登入表單 -->
            <form id="loginForm" action="api/auth/login.php" method="POST">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="loginEmail" name="email" placeholder="name@example.com" required>
                    <label for="loginEmail">電子郵件</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="loginPassword" name="password" placeholder="Password" required>
                    <label for="loginPassword">密碼</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                    <label class="form-check-label" for="rememberMe">
                        記住我
                    </label>
                </div>
                <button type="submit" class="btn btn-auth">登入</button>
                <div class="text-center mb-3">
                    <a href="#" class="text-muted">忘記密碼？</a>
                </div>
            </form>

            <!-- 註冊表單 -->
            <form id="registerForm" action="api/auth/register.php" method="POST" style="display: none;">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="registerName" name="name" placeholder="Your Name" required>
                    <label for="registerName">姓名</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="registerEmail" name="email" placeholder="name@example.com" required>
                    <label for="registerEmail">電子郵件</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="registerPassword" name="password" placeholder="Password" required>
                    <label for="registerPassword">密碼</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                    <label for="confirmPassword">確認密碼</label>
                </div>
                <button type="submit" class="btn btn-auth">註冊</button>
            </form>

            <div class="divider">或</div>

            <div class="social-login">
                <a href="#" class="social-btn btn-facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" class="social-btn btn-google"><i class="bi bi-google"></i></a>
                <a href="#" class="social-btn btn-line"><i class="bi bi-line"></i></a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const tabs = document.querySelectorAll('.auth-tab');

            tabs.forEach(t => t.classList.remove('active'));

            if (tab === 'login') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                tabs[0].classList.add('active');
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                tabs[1].classList.add('active');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 註冊表單處理
            document.getElementById('registerForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const password = document.getElementById('registerPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (password !== confirmPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: '註冊失敗',
                        text: '密碼與確認密碼不符'
                    });
                    return;
                }

                try {
                    const formData = new FormData(this);
                    const response = await axios.post('/CampExplorer/member/api/auth/register.php', formData);

                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '註冊成功',
                            text: '請使用新帳號登入',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            switchTab('login');
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: '註冊失敗',
                        text: error.response?.data?.message || '請稍後再試'
                    });
                }
            });


            // 登入表單處理
            document.getElementById('loginForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                try {
                    const formData = new FormData(this);
                    const response = await axios.post('/CampExplorer/member/api/auth/login.php', formData);

                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '登入成功',
                            text: '即將進入會員中心',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = '/CampExplorer/member/dashboard.php';
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: '登入失敗',
                        text: error.response?.data?.message || '請檢查帳號密碼是否正確'
                    });
                }
            });
        });
    </script>
</body>

</html>