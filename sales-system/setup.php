<?php
/**
 * DONA PAPER - Setup Wizard
 * First-time setup to change default admin password
 *
 * IMPORTANT: Delete this file after completing setup!
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';

// Check if setup is already completed
if (file_exists(__DIR__ . '/.setup_completed')) {
    die('Setup already completed. Delete .setup_completed file to run again.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Mật khẩu mới không khớp';
    } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        $error = 'Mật khẩu phải có ít nhất ' . PASSWORD_MIN_LENGTH . ' ký tự';
    } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        $error = 'Mật khẩu phải chứa chữ hoa, chữ thường và số';
    } else {
        try {
            $db = getDBConnection();

            // Verify current credentials
            $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ? AND is_admin = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Tài khoản admin không tồn tại';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $error = 'Mật khẩu hiện tại không đúng';
            } else {
                // Update password
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newHash, $user['id']]);

                // Mark setup as completed
                file_put_contents(__DIR__ . '/.setup_completed', date('Y-m-d H:i:s'));

                $success = 'Đã thay đổi mật khẩu thành công! Hệ thống sẵn sàng sử dụng.';

                // Auto redirect after 3 seconds
                header('refresh:3;url=/admin/login.php');
            }
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DONA Paper - Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .setup-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }

        .alert-warning {
            background: #ffc;
            color: #c90;
            border-left: 4px solid #c90;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .security-note {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 13px;
            color: #666;
        }

        .security-note strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <h1>DONA PAPER</h1>
            <p>Cài đặt hệ thống lần đầu</p>
        </div>

        <div class="alert alert-warning">
            <strong>Bước quan trọng:</strong> Vui lòng thay đổi mật khẩu admin mặc định để bảo mật hệ thống.
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <br><small>Đang chuyển hướng đến trang đăng nhập...</small>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Tên đăng nhập Admin</label>
                    <input type="text" id="username" name="username" value="admin" required>
                    <div class="help-text">Mặc định: admin</div>
                </div>

                <div class="form-group">
                    <label for="current_password">Mật khẩu hiện tại</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <div class="help-text">Mật khẩu mặc định: Admin@123</div>
                </div>

                <div class="form-group">
                    <label for="new_password">Mật khẩu mới</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <div class="help-text">Tối thiểu 8 ký tự, có chữ hoa, chữ thường và số</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu mới</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn">Thay đổi mật khẩu</button>
            </form>

            <div class="security-note">
                <strong>Lưu ý bảo mật:</strong><br>
                - Sau khi hoàn tất, vui lòng XÓA file setup.php này<br>
                - Không sử dụng mật khẩu mặc định trong môi trường production<br>
                - Chọn mật khẩu mạnh với ít nhất 12 ký tự
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
