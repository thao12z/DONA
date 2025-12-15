<?php
/**
 * PHP Version Compatibility Check
 * Ensures minimum PHP version requirements are met
 */

// Minimum required PHP version
define('MIN_PHP_VERSION', '7.4.0');

if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
    header('Content-Type: text/html; charset=utf-8');
    http_response_code(500);

    $errorMessage = sprintf(
        'PHP version %s or higher is required. You are currently running PHP %s.',
        MIN_PHP_VERSION,
        PHP_VERSION
    );

    // Check if request is AJAX/API
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $errorMessage
        ]);
    } else {
        // HTML error page for browser requests
        echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Version Error</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            color: #e74c3c;
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 10px;
        }
        p {
            color: #7f8c8d;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .version-info {
            background: #f8f9fa;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            text-align: left;
            margin-top: 20px;
        }
        .version-info strong {
            color: #2c3e50;
        }
        .solution {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 15px;
            text-align: left;
            margin-top: 20px;
        }
        .solution h2 {
            color: #2c3e50;
            font-size: 18px;
            margin-top: 0;
        }
        .solution ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .solution li {
            margin: 5px 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠</div>
        <h1>Lỗi phiên bản PHP</h1>
        <p>Hệ thống DONA Paper yêu cầu phiên bản PHP mới hơn để hoạt động.</p>

        <div class="version-info">
            <strong>Yêu cầu:</strong> PHP ' . htmlspecialchars(MIN_PHP_VERSION) . ' hoặc cao hơn<br>
            <strong>Hiện tại:</strong> PHP ' . htmlspecialchars(PHP_VERSION) . '
        </div>

        <div class="solution">
            <h2>Giải pháp</h2>
            <ul>
                <li>Liên hệ nhà cung cấp hosting để nâng cấp PHP</li>
                <li>Truy cập cPanel → Select PHP Version → Chọn PHP 7.4 trở lên</li>
                <li>Hoặc cập nhật file .htaccess với dòng: <code>AddHandler application/x-httpd-php74 .php</code></li>
            </ul>
        </div>
    </div>
</body>
</html>';
    }

    exit(1);
}

// Version check passed - continue loading application
