<?php
/**
 * Authentication System
 */

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();

        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');

            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            session_start();
        }

        $this->refreshSession();
    }

    /**
     * Login user
     */
    public function login($username, $password, $rememberMe = false) {
        $ipAddress = getClientIP();

        if ($this->isLoginRateLimited($username, $ipAddress)) {
            return [
                'success' => false,
                'message' => 'Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau 15 phút.'
            ];
        }

        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ? AND is_active = 1",
            [$username]
        );

        if (!$user) {
            $this->logLoginAttempt($username, $ipAddress, false);
            return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'];
        }

        if ($user['is_banned'] && (!$user['ban_until'] || strtotime($user['ban_until']) > time())) {
            return ['success' => false, 'message' => 'Tài khoản của bạn đã bị khóa'];
        }

        if (!password_verify($password, $user['password'])) {
            $this->logLoginAttempt($username, $ipAddress, false);
            return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'];
        }

        $this->logLoginAttempt($username, $ipAddress, true);

        $this->db->update('users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $this->db->update('users',
                ['remember_token' => $token],
                'id = ?',
                [$user['id']]
            );

            setcookie('remember_token', $token, time() + (86400 * 7), '/', '', true, true);
        }

        logActivity($user['id'], 'LOGIN', 'Successful login');

        return [
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'is_admin' => (bool)$user['is_admin']
            ]
        ];
    }

    /**
     * Logout user
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            logActivity($_SESSION['user_id'], 'LOGOUT', 'User logged out');
        }

        $_SESSION = [];

        if (isset($_COOKIE[SESSION_NAME])) {
            setcookie(SESSION_NAME, '', time() - 3600, '/');
        }

        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }

        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['login_time']);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->fetchOne(
            "SELECT id, username, full_name, is_admin FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }

    /**
     * Get current user ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Require login
     */
    public function requireLogin($adminOnly = false) {
        if (!$this->isLoggedIn()) {
            if ($this->isAjaxRequest()) {
                errorResponse('Vui lòng đăng nhập', 401);
            } else {
                redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            }
        }

        if ($adminOnly && !$this->isAdmin()) {
            if ($this->isAjaxRequest()) {
                errorResponse('Không có quyền truy cập', 403);
            } else {
                redirect('/');
            }
        }
    }

    /**
     * Refresh session
     */
    private function refreshSession() {
        if (!$this->isLoggedIn()) {
            return;
        }

        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $currentTime = time();

        if ($currentTime - $lastActivity > SESSION_LIFETIME) {
            $this->logout();
            return;
        }

        $_SESSION['last_activity'] = $currentTime;
    }

    /**
     * Check if login is rate limited
     */
    private function isLoginRateLimited($username, $ipAddress) {
        $fifteenMinutesAgo = date('Y-m-d H:i:s', strtotime('-15 minutes'));

        $failedAttempts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM login_attempts
             WHERE (username = ? OR ip_address = ?)
             AND success = 0
             AND attempted_at > ?",
            [$username, $ipAddress, $fifteenMinutesAgo]
        );

        return $failedAttempts >= 5;
    }

    /**
     * Log login attempt
     */
    private function logLoginAttempt($username, $ipAddress, $success) {
        $this->db->insert('login_attempts', [
            'username' => $username,
            'ip_address' => $ipAddress,
            'success' => $success ? 1 : 0
        ]);

        $oneDayAgo = date('Y-m-d H:i:s', strtotime('-1 day'));
        $this->db->delete('login_attempts', 'attempted_at < ?', [$oneDayAgo]);
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);

        if (!$user) {
            return ['success' => false, 'message' => 'Người dùng không tồn tại'];
        }

        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'];
        }

        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất ' . PASSWORD_MIN_LENGTH . ' ký tự'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->db->update('users',
            ['password' => $hashedPassword],
            'id = ?',
            [$userId]
        );

        logActivity($userId, 'CHANGE_PASSWORD', 'Password changed successfully');

        return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];
    }

    /**
     * Create user
     */
    public function createUser($data) {
        $requiredFields = ['username', 'password', 'full_name'];
        $errors = validateRequired($data, $requiredFields);

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(', ', $errors)];
        }

        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ?",
            [$data['username']]
        );

        if ($existingUser) {
            return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        $userId = $this->db->insert('users', $data);

        logActivity($userId, 'USER_CREATED', 'New user created: ' . $data['username']);

        return ['success' => true, 'message' => 'Tạo người dùng thành công', 'user_id' => $userId];
    }
}

function auth() {
    static $auth = null;
    if ($auth === null) {
        $auth = new Auth();
    }
    return $auth;
}
