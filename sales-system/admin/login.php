<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (auth()->isLoggedIn()) {
    if (auth()->isAdmin()) {
        redirect('/admin/index.php');
    } else {
        redirect('/user/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Đăng nhập Admin - DONA Paper</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="bg-gray">
    <div class="container" style="max-width: 450px; margin-top: 5rem;">
        <div class="card">
            <div class="text-center mb-3">
                <h1 class="text-primary">DONA PAPER</h1>
                <p class="text-muted">Quản trị hệ thống</p>
            </div>

            <form id="login-form">
                <div class="form-group">
                    <label class="form-label form-label-required">Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label form-label-required">Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
            </form>
        </div>

        <p class="text-center text-muted mt-3" style="font-size: 12px;">
            DONA Paper Admin &copy; <?php echo date('Y'); ?>
        </p>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!App.validateForm(e.target)) return;

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');

            App.showLoading(submitBtn);

            try {
                const response = await App.post('auth.php?action=login', {
                    username: formData.get('username'),
                    password: formData.get('password'),
                    remember_me: 'false'
                });

                if (response.success) {
                    if (response.data.is_admin) {
                        App.showSuccess('Đăng nhập thành công!');
                        setTimeout(() => window.location.href = '/admin/index.php', 500);
                    } else {
                        App.showError('Bạn không có quyền truy cập admin');
                    }
                }
            } catch (error) {
                // Error shown by App.post
            } finally {
                App.hideLoading(submitBtn);
            }
        });
    </script>
</body>
</html>
