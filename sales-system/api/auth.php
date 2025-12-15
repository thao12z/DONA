<?php
/**
 * Authentication API
 * Handles login, logout, and session checking
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/spam_protection.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$auth = auth();

switch ($method) {
    case 'POST':
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        if ($action === 'login') {
            handleLogin();
        } elseif ($action === 'logout') {
            handleLogout();
        } elseif ($action === 'change-password') {
            handleChangePassword();
        } else {
            errorResponse('Invalid action', 400);
        }
        break;

    case 'GET':
        $action = $_GET['action'] ?? '';

        if ($action === 'check-session') {
            handleCheckSession();
        } elseif ($action === 'me') {
            handleGetCurrentUser();
        } else {
            errorResponse('Invalid action', 400);
        }
        break;

    default:
        errorResponse('Method not allowed', 405);
}

function handleLogin() {
    requireSpamCheck('login');

    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === 'true';

    if (empty($username) || empty($password)) {
        errorResponse('Vui lòng nhập tên đăng nhập và mật khẩu', 400);
    }

    $result = auth()->login($username, $password, $rememberMe);

    if ($result['success']) {
        successResponse($result['message'], $result['user']);
    } else {
        errorResponse($result['message'], 401);
    }
}

function handleLogout() {
    auth()->logout();
    successResponse('Đăng xuất thành công');
}

function handleCheckSession() {
    if (auth()->isLoggedIn()) {
        successResponse('Session active', [
            'logged_in' => true,
            'is_admin' => auth()->isAdmin()
        ]);
    } else {
        successResponse('No session', ['logged_in' => false]);
    }
}

function handleGetCurrentUser() {
    auth()->requireLogin();

    $user = auth()->getCurrentUser();

    if ($user) {
        successResponse('', $user);
    } else {
        errorResponse('User not found', 404);
    }
}

function handleChangePassword() {
    auth()->requireLogin();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        errorResponse('Vui lòng điền đầy đủ thông tin', 400);
    }

    if ($newPassword !== $confirmPassword) {
        errorResponse('Mật khẩu xác nhận không khớp', 400);
    }

    $result = auth()->changePassword(auth()->getUserId(), $currentPassword, $newPassword);

    if ($result['success']) {
        successResponse($result['message']);
    } else {
        errorResponse($result['message'], 400);
    }
}
