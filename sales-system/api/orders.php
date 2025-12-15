<?php
/**
 * Orders API
 * CRUD operations for orders
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/spam_protection.php';

header('Content-Type: application/json; charset=utf-8');

$auth = auth();
$auth->requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$db = db();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getOrder($_GET['id']);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'export') {
            exportOrders();
        } else {
            listOrders();
        }
        break;

    case 'POST':
        createOrder();
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $_PUT);
        updateOrder($_PUT);
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            deleteOrder($_GET['id']);
        } else {
            errorResponse('Order ID required', 400);
        }
        break;

    default:
        errorResponse('Method not allowed', 405);
}

/**
 * List orders with filters
 */
function listOrders() {
    global $auth, $db;

    $userId = $auth->getUserId();
    $isAdmin = $auth->isAdmin();

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : ITEMS_PER_PAGE;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if (!$isAdmin) {
        $where[] = "user_id = ?";
        $params[] = $userId;
    }

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where[] = "status = ?";
        $params[] = sanitize($_GET['status']);
    }

    if (isset($_GET['user_id']) && !empty($_GET['user_id']) && $isAdmin) {
        $where[] = "user_id = ?";
        $params[] = (int)$_GET['user_id'];
    }

    if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = sanitize($_GET['from_date']);
    }

    if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = sanitize($_GET['to_date']);
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $where[] = "(customer_name LIKE ? OR customer_phone LIKE ?)";
        $params[] = $search;
        $params[] = $search;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = $db->fetchColumn(
        "SELECT COUNT(*) FROM orders {$whereClause}",
        $params
    );

    $orders = $db->fetchAll(
        "SELECT o.*, u.full_name as user_name, u.position as user_position
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         {$whereClause}
         ORDER BY o.created_at DESC
         LIMIT {$limit} OFFSET {$offset}",
        $params
    );

    $pagination = paginate($total, $limit, $page);

    successResponse('', [
        'orders' => $orders,
        'pagination' => $pagination
    ]);
}

/**
 * Get single order
 */
function getOrder($id) {
    global $auth, $db;

    $order = $db->fetchOne(
        "SELECT o.*, u.full_name as user_name, u.contact_info as user_contact,
         admin.full_name as admin_name
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         LEFT JOIN users admin ON o.admin_id = admin.id
         WHERE o.id = ?",
        [(int)$id]
    );

    if (!$order) {
        errorResponse('Order not found', 404);
    }

    if (!$auth->isAdmin() && $order['user_id'] != $auth->getUserId()) {
        errorResponse('Access denied', 403);
    }

    successResponse('', $order);
}

/**
 * Create new order
 */
function createOrder() {
    global $auth;

    requireSpamCheck('create_order');

    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $requiredFields = [
        'customer_name', 'customer_phone', 'customer_address',
        'customer_type', 'purchase_price', 'quantity', 'total_revenue'
    ];

    $errors = validateRequired($data, $requiredFields);
    if (!empty($errors)) {
        errorResponse(implode(', ', $errors), 400);
    }

    if (!isValidPhone($data['customer_phone'])) {
        errorResponse('Số điện thoại không hợp lệ', 400);
    }

    $orderData = [
        'user_id' => $auth->getUserId(),
        'customer_name' => sanitize($data['customer_name']),
        'customer_phone' => sanitize($data['customer_phone']),
        'customer_address' => sanitize($data['customer_address']),
        'customer_social' => sanitize($data['customer_social'] ?? ''),
        'customer_latitude' => $data['customer_latitude'] ?? null,
        'customer_longitude' => $data['customer_longitude'] ?? null,
        'customer_type' => sanitize($data['customer_type']),
        'purchase_price' => (float)$data['purchase_price'],
        'quantity' => (int)$data['quantity'],
        'total_revenue' => (float)$data['total_revenue'],
        'other_costs' => (float)($data['other_costs'] ?? 0),
        'notes' => sanitize($data['notes'] ?? ''),
        'status' => 'pending'
    ];

    try {
        $orderId = db()->insert('orders', $orderData);

        logActivity($auth->getUserId(), 'CREATE_ORDER', "Order #{$orderId} created");

        successResponse('Tạo đơn hàng thành công', [
            'order_id' => $orderId
        ]);
    } catch (Exception $e) {
        errorResponse('Failed to create order: ' . $e->getMessage(), 500);
    }
}

/**
 * Update order (admin only - approve/reject)
 */
function updateOrder($data) {
    global $auth;

    $auth->requireLogin(true);

    if (!isset($data['id'])) {
        errorResponse('Order ID required', 400);
    }

    $orderId = (int)$data['id'];

    $order = db()->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    if (!$order) {
        errorResponse('Order not found', 404);
    }

    $updateData = [];

    if (isset($data['status'])) {
        $allowedStatuses = ['pending', 'approved', 'rejected'];
        $status = sanitize($data['status']);

        if (!in_array($status, $allowedStatuses)) {
            errorResponse('Invalid status', 400);
        }

        $updateData['status'] = $status;
        $updateData['admin_id'] = $auth->getUserId();
        $updateData['approved_at'] = date('Y-m-d H:i:s');

        if ($status === 'rejected' && isset($data['admin_message'])) {
            $updateData['admin_message'] = sanitize($data['admin_message']);
        }
    }

    if (isset($data['admin_message'])) {
        $updateData['admin_message'] = sanitize($data['admin_message']);
    }

    if (empty($updateData)) {
        errorResponse('No data to update', 400);
    }

    try {
        db()->update('orders', $updateData, 'id = ?', [$orderId]);

        logActivity($auth->getUserId(), 'UPDATE_ORDER', "Order #{$orderId} updated to status: " . ($data['status'] ?? 'N/A'));

        successResponse('Cập nhật đơn hàng thành công');
    } catch (Exception $e) {
        errorResponse('Failed to update order: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete order (admin only)
 */
function deleteOrder($id) {
    global $auth;

    $auth->requireLogin(true);

    $orderId = (int)$id;

    $order = db()->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    if (!$order) {
        errorResponse('Order not found', 404);
    }

    try {
        db()->delete('orders', 'id = ?', [$orderId]);

        logActivity($auth->getUserId(), 'DELETE_ORDER', "Order #{$orderId} deleted");

        successResponse('Xóa đơn hàng thành công');
    } catch (Exception $e) {
        errorResponse('Failed to delete order: ' . $e->getMessage(), 500);
    }
}

/**
 * Export orders to CSV (admin only)
 */
function exportOrders() {
    global $auth;

    $auth->requireLogin(true);

    $where = [];
    $params = [];

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where[] = "status = ?";
        $params[] = sanitize($_GET['status']);
    }

    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $where[] = "user_id = ?";
        $params[] = (int)$_GET['user_id'];
    }

    if (isset($_GET['from_date']) && !empty($_GET['from_date'])) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = sanitize($_GET['from_date']);
    }

    if (isset($_GET['to_date']) && !empty($_GET['to_date'])) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = sanitize($_GET['to_date']);
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $orders = db()->fetchAll(
        "SELECT o.*, u.full_name as user_name
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         {$whereClause}
         ORDER BY o.created_at DESC",
        $params
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=orders_' . date('Y-m-d') . '.csv');

    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'ID', 'Nhân viên', 'Khách hàng', 'Điện thoại', 'Địa chỉ',
        'Loại KH', 'Giá nhập', 'Số lượng', 'Tổng thu', 'Chi phí khác',
        'Lợi nhuận', 'Trạng thái', 'Ngày tạo'
    ]);

    foreach ($orders as $order) {
        fputcsv($output, [
            $order['id'],
            $order['user_name'],
            $order['customer_name'],
            $order['customer_phone'],
            $order['customer_address'],
            $order['customer_type'],
            $order['purchase_price'],
            $order['quantity'],
            $order['total_revenue'],
            $order['other_costs'],
            $order['profit'],
            $order['status'],
            formatDate($order['created_at'])
        ]);
    }

    fclose($output);
    exit;
}
