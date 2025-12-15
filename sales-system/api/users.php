<?php
/**
 * Users Management API
 * Admin only - CRUD operations for users
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// CSRF Protection for state-changing requests
requireCSRF();

$auth = auth();
$auth->requireLogin(true); // Admin only

$method = $_SERVER['REQUEST_METHOD'];
$db = db();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getUser($_GET['id']);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'stats') {
            getUserStats();
        } else {
            listUsers();
        }
        break;

    case 'POST':
        // Check if this is a PUT request disguised as POST (for FormData)
        if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
            updateUser($_POST);
        } else {
            createUser();
        }
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $_PUT);
        updateUser($_PUT);
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteUser($_GET['id']);
        } else {
            errorResponse('User ID required', 400);
        }
        break;

    default:
        errorResponse('Method not allowed', 405);
}

/**
 * List all users with filters
 */
function listUsers() {
    global $db;

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : ITEMS_PER_PAGE;
    $offset = ($page - 1) * $limit;

    $where = ['is_admin = 0']; // Exclude admins from user list
    $params = [];

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $where[] = "(full_name LIKE ? OR username LIKE ? OR contact_info LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
        $where[] = "is_active = ?";
        $params[] = (int)$_GET['is_active'];
    }

    if (isset($_GET['province_id']) && !empty($_GET['province_id'])) {
        $where[] = "province_id = ?";
        $params[] = (int)$_GET['province_id'];
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    $total = $db->fetchColumn(
        "SELECT COUNT(*) FROM users {$whereClause}",
        $params
    );

    $users = $db->fetchAll(
        "SELECT u.*,
         p.name as province_name,
         d.name as district_name,
         w.name as ward_name,
         (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
         (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status = 'approved') as approved_orders,
         (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status = 'rejected') as rejected_orders,
         (SELECT SUM(total_revenue) FROM orders WHERE user_id = u.id AND status = 'approved') as total_revenue
         FROM users u
         LEFT JOIN provinces p ON u.province_id = p.id
         LEFT JOIN districts d ON u.district_id = d.id
         LEFT JOIN wards w ON u.ward_id = w.id
         {$whereClause}
         ORDER BY u.created_at DESC
         LIMIT {$limit} OFFSET {$offset}",
        $params
    );

    // Calculate rejection rate
    foreach ($users as &$user) {
        $user['rejection_rate'] = $user['total_orders'] > 0
            ? round(($user['rejected_orders'] / $user['total_orders']) * 100, 1)
            : 0;

        // Remove sensitive data
        unset($user['password']);
    }

    $pagination = paginate($total, $limit, $page);

    successResponse('', [
        'users' => $users,
        'pagination' => $pagination
    ]);
}

/**
 * Get single user details
 */
function getUser($id) {
    global $db;

    $user = $db->fetchOne(
        "SELECT u.*,
         p.name as province_name,
         d.name as district_name,
         w.name as ward_name,
         (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
         (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status = 'approved') as approved_orders,
         (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status = 'rejected') as rejected_orders,
         (SELECT SUM(total_revenue) FROM orders WHERE user_id = u.id AND status = 'approved') as total_revenue,
         (SELECT SUM(profit) FROM orders WHERE user_id = u.id AND status = 'approved') as total_profit
         FROM users u
         LEFT JOIN provinces p ON u.province_id = p.id
         LEFT JOIN districts d ON u.district_id = d.id
         LEFT JOIN wards w ON u.ward_id = w.id
         WHERE u.id = ?",
        [(int)$id]
    );

    if (!$user) {
        errorResponse('User not found', 404);
    }

    unset($user['password']);

    $user['rejection_rate'] = $user['total_orders'] > 0
        ? round(($user['rejected_orders'] / $user['total_orders']) * 100, 1)
        : 0;

    successResponse('', $user);
}

/**
 * Create new user
 */
function createUser() {
    global $auth;

    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $requiredFields = ['username', 'password', 'full_name'];
    $errors = validateRequired($data, $requiredFields);

    if (!empty($errors)) {
        errorResponse(implode(', ', $errors), 400);
    }

    // Check if username exists
    $existing = db()->fetchOne("SELECT id FROM users WHERE username = ?", [$data['username']]);
    if ($existing) {
        errorResponse('Tên đăng nhập đã tồn tại', 400);
    }

    // Handle file uploads (ID cards)
    $idCardFront = null;
    $idCardBack = null;

    if (isset($_FILES['id_card_front']) && $_FILES['id_card_front']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['id_card_front'], ID_CARD_PATH);
        if ($uploadResult['success']) {
            $idCardFront = $uploadResult['filename'];
        }
    }

    if (isset($_FILES['id_card_back']) && $_FILES['id_card_back']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['id_card_back'], ID_CARD_PATH);
        if ($uploadResult['success']) {
            $idCardBack = $uploadResult['filename'];
        }
    }

    $userData = [
        'username' => sanitize($data['username']),
        'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        'full_name' => sanitize($data['full_name']),
        'date_of_birth' => $data['date_of_birth'] ?? null,
        'gender' => isset($data['gender']) ? sanitize($data['gender']) : null,
        'address_detail' => isset($data['address_detail']) ? sanitize($data['address_detail']) : null,
        'id_card_front' => $idCardFront,
        'id_card_back' => $idCardBack,
        'province_id' => isset($data['province_id']) ? (int)$data['province_id'] : null,
        'district_id' => isset($data['district_id']) ? (int)$data['district_id'] : null,
        'ward_id' => isset($data['ward_id']) ? (int)$data['ward_id'] : null,
        'position' => isset($data['position']) ? sanitize($data['position']) : null,
        'contact_info' => isset($data['contact_info']) ? sanitize($data['contact_info']) : null,
        'is_admin' => isset($data['is_admin']) && $data['is_admin'] ? 1 : 0,
        'is_active' => 1
    ];

    try {
        $userId = db()->insert('users', $userData);

        logActivity($auth->getUserId(), 'CREATE_USER', "Created user ID: {$userId}, username: {$userData['username']}");

        successResponse('Tạo người dùng thành công', ['user_id' => $userId]);
    } catch (Exception $e) {
        errorResponse('Failed to create user: ' . $e->getMessage(), 500);
    }
}

/**
 * Update user
 */
function updateUser($data) {
    global $auth;

    if (!isset($data['id'])) {
        errorResponse('User ID required', 400);
    }

    $userId = (int)$data['id'];

    $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        errorResponse('User not found', 404);
    }

    $updateData = [];

    $allowedFields = ['full_name', 'date_of_birth', 'gender', 'address_detail', 'province_id',
                      'district_id', 'ward_id', 'position', 'contact_info', 'is_active', 'username'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if (in_array($field, ['province_id', 'district_id', 'ward_id', 'is_active'])) {
                $updateData[$field] = (int)$data[$field];
            } else {
                $updateData[$field] = sanitize($data[$field]);
            }
        }
    }

    // Handle is_admin
    if (isset($data['is_admin'])) {
        $updateData['is_admin'] = $data['is_admin'] ? 1 : 0;
    }

    // Handle password change
    if (isset($data['password']) && !empty($data['password'])) {
        $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }

    // Handle file uploads (ID cards)
    if (isset($_FILES['id_card_front']) && $_FILES['id_card_front']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['id_card_front'], ID_CARD_PATH);
        if ($uploadResult['success']) {
            // Delete old file
            if ($user['id_card_front']) {
                deleteFile(ID_CARD_PATH . '/' . $user['id_card_front']);
            }
            $updateData['id_card_front'] = $uploadResult['filename'];
        }
    }

    if (isset($_FILES['id_card_back']) && $_FILES['id_card_back']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['id_card_back'], ID_CARD_PATH);
        if ($uploadResult['success']) {
            // Delete old file
            if ($user['id_card_back']) {
                deleteFile(ID_CARD_PATH . '/' . $user['id_card_back']);
            }
            $updateData['id_card_back'] = $uploadResult['filename'];
        }
    }

    // Handle ban/unban
    if (isset($data['is_banned'])) {
        $updateData['is_banned'] = (int)$data['is_banned'];
        $updateData['ban_until'] = $data['is_banned'] ? null : null;
    }

    if (empty($updateData)) {
        errorResponse('No data to update', 400);
    }

    try {
        db()->update('users', $updateData, 'id = ?', [$userId]);

        logActivity($auth->getUserId(), 'UPDATE_USER', "Updated user ID: {$userId}");

        successResponse('Cập nhật người dùng thành công');
    } catch (Exception $e) {
        errorResponse('Failed to update user: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete user
 */
function deleteUser($id) {
    global $auth;

    $userId = (int)$id;

    $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        errorResponse('User not found', 404);
    }

    if ($user['is_admin']) {
        errorResponse('Cannot delete admin user', 403);
    }

    try {
        // Delete ID card images
        if ($user['id_card_front']) {
            deleteFile(ID_CARD_PATH . '/' . $user['id_card_front']);
        }
        if ($user['id_card_back']) {
            deleteFile(ID_CARD_PATH . '/' . $user['id_card_back']);
        }

        db()->delete('users', 'id = ?', [$userId]);

        logActivity($auth->getUserId(), 'DELETE_USER', "Deleted user ID: {$userId}, username: {$user['username']}");

        successResponse('Xóa người dùng thành công');
    } catch (Exception $e) {
        errorResponse('Failed to delete user: ' . $e->getMessage(), 500);
    }
}

/**
 * Get user statistics
 */
function getUserStats() {
    global $db;

    $stats = [
        'total_users' => $db->fetchColumn("SELECT COUNT(*) FROM users WHERE is_admin = 0"),
        'active_users' => $db->fetchColumn("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND is_active = 1"),
        'banned_users' => $db->fetchColumn("SELECT COUNT(*) FROM users WHERE is_banned = 1"),
        'users_with_orders' => $db->fetchColumn("SELECT COUNT(DISTINCT user_id) FROM orders"),
    ];

    successResponse('', $stats);
}
