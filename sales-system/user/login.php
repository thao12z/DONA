<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to dashboard
if (auth()->isLoggedIn()) {
    redirect('/user/index.php');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Đăng nhập - DONA Paper</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body class="bg-gray">
    <div class="container" style="max-width: 450px; margin-top: 5rem;">
        <div class="card">
            <div class="text-center mb-3">
                <h1 class="text-primary">DONA PAPER</h1>
                <p class="text-muted">Hệ thống quản lý bán hàng</p>
            </div>

            <div id="alert-container"></div>

            <form id="login-form">
                <div class="form-group">
                    <label class="form-label form-label-required">Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label form-label-required">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="checkbox">
                        <input type="checkbox" name="remember_me">
                        Ghi nhớ đăng nhập
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
            </form>
        </div>

        <p class="text-center text-muted mt-3" style="font-size: 12px;">
            DONA Paper &copy; <?php echo date('Y'); ?>. Hệ thống dành cho nhân viên kinh doanh.
        </p>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!App.validateForm(e.target)) {
                return;
            }

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');

            App.showLoading(submitBtn);

            try {
                const response = await App.post('auth.php?action=login', {
                    username: formData.get('username'),
                    password: formData.get('password'),
                    remember_me: formData.get('remember_me') ? 'true' : 'false'
                });

                if (response.success) {
                    App.showSuccess('Đăng nhập thành công!');
                    setTimeout(() => {
                        window.location.href = '/user/index.php';
                    }, 500);
                } else {
                    App.showError(response.error || 'Đăng nhập thất bại');
                }
            } catch (error) {
                App.showError('Đăng nhập thất bại. Vui lòng thử lại.');
            } finally {
                App.hideLoading(submitBtn);
            }
        });
    </script>
</body>
</html>
