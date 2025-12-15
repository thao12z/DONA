<?php
/**
 * Reports & Statistics API
 * Business intelligence and analytics
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$auth = auth();
$auth->requireLogin(true); // Admin only

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    errorResponse('Method not allowed', 405);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard':
        getDashboardStats();
        break;

    case 'revenue':
        getRevenueReport();
        break;

    case 'user-performance':
        getUserPerformance();
        break;

    case 'orders-trend':
        getOrdersTrend();
        break;

    case 'top-users':
        getTopUsers();
        break;

    case 'location-stats':
        getLocationStats();
        break;

    default:
        errorResponse('Invalid action', 400);
}

/**
 * Dashboard statistics
 */
function getDashboardStats() {
    $db = db();

    $today = date('Y-m-d');
    $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
    $thisMonthStart = date('Y-m-01');

    $stats = [
        // Orders
        'orders_today' => $db->fetchColumn(
            "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?",
            [$today]
        ),
        'orders_this_week' => $db->fetchColumn(
            "SELECT COUNT(*) FROM orders WHERE DATE(created_at) >= ?",
            [$thisWeekStart]
        ),
        'orders_this_month' => $db->fetchColumn(
            "SELECT COUNT(*) FROM orders WHERE DATE(created_at) >= ?",
            [$thisMonthStart]
        ),
        'orders_pending' => $db->fetchColumn(
            "SELECT COUNT(*) FROM orders WHERE status = 'pending'"
        ),
        'orders_approved' => $db->fetchColumn(
            "SELECT COUNT(*) FROM orders WHERE status = 'approved'"
        ),
        'orders_rejected' => $db->fetchColumn(
            "SELECT COUNT(*) FROM orders WHERE status = 'rejected'"
        ),

        // Revenue
        'revenue_today' => (float)$db->fetchColumn(
            "SELECT SUM(total_revenue) FROM orders WHERE DATE(created_at) = ? AND status = 'approved'",
            [$today]
        ) ?: 0,
        'revenue_this_week' => (float)$db->fetchColumn(
            "SELECT SUM(total_revenue) FROM orders WHERE DATE(created_at) >= ? AND status = 'approved'",
            [$thisWeekStart]
        ) ?: 0,
        'revenue_this_month' => (float)$db->fetchColumn(
            "SELECT SUM(total_revenue) FROM orders WHERE DATE(created_at) >= ? AND status = 'approved'",
            [$thisMonthStart]
        ) ?: 0,

        // Profit
        'profit_today' => (float)$db->fetchColumn(
            "SELECT SUM(profit) FROM orders WHERE DATE(created_at) = ? AND status = 'approved'",
            [$today]
        ) ?: 0,
        'profit_this_week' => (float)$db->fetchColumn(
            "SELECT SUM(profit) FROM orders WHERE DATE(created_at) >= ? AND status = 'approved'",
            [$thisWeekStart]
        ) ?: 0,
        'profit_this_month' => (float)$db->fetchColumn(
            "SELECT SUM(profit) FROM orders WHERE DATE(created_at) >= ? AND status = 'approved'",
            [$thisMonthStart]
        ) ?: 0,

        // Users
        'total_users' => $db->fetchColumn("SELECT COUNT(*) FROM users WHERE is_admin = 0"),
        'active_users' => $db->fetchColumn("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND is_active = 1"),
    ];

    $stats['approval_rate'] = $stats['orders_today'] > 0
        ? round(($stats['orders_approved'] / ($stats['orders_approved'] + $stats['orders_rejected'])) * 100, 1)
        : 0;

    successResponse('', $stats);
}

/**
 * Revenue report with date range
 */
function getRevenueReport() {
    $db = db();

    $fromDate = $_GET['from_date'] ?? date('Y-m-01');
    $toDate = $_GET['to_date'] ?? date('Y-m-d');

    // Validate dates if provided by user
    if (isset($_GET['from_date']) && !isValidDate($fromDate)) {
        errorResponse('Invalid from_date format. Expected: YYYY-MM-DD', 400);
    }
    if (isset($_GET['to_date']) && !isValidDate($toDate)) {
        errorResponse('Invalid to_date format. Expected: YYYY-MM-DD', 400);
    }

    $groupBy = $_GET['group_by'] ?? 'day'; // day, week, month

    // PHP 7 compatible date format selection
    if ($groupBy === 'week') {
        $dateFormat = '%Y-%u';
    } elseif ($groupBy === 'month') {
        $dateFormat = '%Y-%m';
    } else {
        $dateFormat = '%Y-%m-%d';
    }

    $revenue = $db->fetchAll(
        "SELECT
            DATE_FORMAT(created_at, ?) as period,
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_orders,
            SUM(CASE WHEN status = 'approved' THEN total_revenue ELSE 0 END) as total_revenue,
            SUM(CASE WHEN status = 'approved' THEN profit ELSE 0 END) as total_profit
         FROM orders
         WHERE DATE(created_at) BETWEEN ? AND ?
         GROUP BY period
         ORDER BY period ASC",
        [$dateFormat, $fromDate, $toDate]
    );

    $summary = [
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'total_orders' => array_sum(array_column($revenue, 'total_orders')),
        'approved_orders' => array_sum(array_column($revenue, 'approved_orders')),
        'total_revenue' => array_sum(array_column($revenue, 'total_revenue')),
        'total_profit' => array_sum(array_column($revenue, 'total_profit')),
    ];

    successResponse('', [
        'summary' => $summary,
        'breakdown' => $revenue
    ]);
}

/**
 * User performance report
 */
function getUserPerformance() {
    $db = db();

    $fromDate = $_GET['from_date'] ?? date('Y-m-01');
    $toDate = $_GET['to_date'] ?? date('Y-m-d');

    // Validate dates if provided by user
    if (isset($_GET['from_date']) && !isValidDate($fromDate)) {
        errorResponse('Invalid from_date format. Expected: YYYY-MM-DD', 400);
    }
    if (isset($_GET['to_date']) && !isValidDate($toDate)) {
        errorResponse('Invalid to_date format. Expected: YYYY-MM-DD', 400);
    }

    $limit = isset($_GET['limit']) ? min(100, (int)$_GET['limit']) : 20;

    $performance = $db->fetchAll(
        "SELECT
            u.id,
            u.full_name,
            u.position,
            COUNT(o.id) as total_orders,
            SUM(CASE WHEN o.status = 'approved' THEN 1 ELSE 0 END) as approved_orders,
            SUM(CASE WHEN o.status = 'rejected' THEN 1 ELSE 0 END) as rejected_orders,
            SUM(CASE WHEN o.status = 'approved' THEN o.total_revenue ELSE 0 END) as total_revenue,
            SUM(CASE WHEN o.status = 'approved' THEN o.profit ELSE 0 END) as total_profit,
            ROUND(AVG(CASE WHEN o.status = 'approved' THEN o.total_revenue ELSE NULL END), 2) as avg_order_value
         FROM users u
         LEFT JOIN orders o ON u.id = o.user_id AND DATE(o.created_at) BETWEEN ? AND ?
         WHERE u.is_admin = 0
         GROUP BY u.id
         HAVING total_orders > 0
         ORDER BY total_revenue DESC
         LIMIT {$limit}",
        [$fromDate, $toDate]
    );

    foreach ($performance as &$user) {
        $user['approval_rate'] = $user['total_orders'] > 0
            ? round(($user['approved_orders'] / $user['total_orders']) * 100, 1)
            : 0;
        $user['rejection_rate'] = $user['total_orders'] > 0
            ? round(($user['rejected_orders'] / $user['total_orders']) * 100, 1)
            : 0;
    }

    successResponse('', [
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'users' => $performance
    ]);
}

/**
 * Orders trend (last 30 days)
 */
function getOrdersTrend() {
    $db = db();

    $days = isset($_GET['days']) ? min(90, (int)$_GET['days']) : 30;
    $fromDate = date('Y-m-d', strtotime("-{$days} days"));

    $trend = $db->fetchAll(
        "SELECT
            DATE(created_at) as date,
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
         FROM orders
         WHERE DATE(created_at) >= ?
         GROUP BY DATE(created_at)
         ORDER BY date ASC",
        [$fromDate]
    );

    successResponse('', ['trend' => $trend]);
}

/**
 * Top performing users
 */
function getTopUsers() {
    $db = db();

    $period = $_GET['period'] ?? 'month'; // today, week, month, all
    $limit = isset($_GET['limit']) ? min(50, (int)$_GET['limit']) : 10;

    // PHP 7 compatible period filtering
    if ($period === 'today') {
        $whereClause = "WHERE DATE(o.created_at) = CURDATE()";
    } elseif ($period === 'week') {
        $whereClause = "WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($period === 'month') {
        $whereClause = "WHERE DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } else {
        $whereClause = "";
    }

    $topUsers = $db->fetchAll(
        "SELECT
            u.id,
            u.full_name,
            u.position,
            p.name as province_name,
            COUNT(o.id) as total_orders,
            SUM(CASE WHEN o.status = 'approved' THEN o.total_revenue ELSE 0 END) as total_revenue,
            SUM(CASE WHEN o.status = 'approved' THEN o.profit ELSE 0 END) as total_profit
         FROM users u
         LEFT JOIN orders o ON u.id = o.user_id
         LEFT JOIN provinces p ON u.province_id = p.id
         {$whereClause}
         WHERE u.is_admin = 0
         GROUP BY u.id
         HAVING total_orders > 0
         ORDER BY total_revenue DESC
         LIMIT {$limit}",
        []
    );

    successResponse('', [
        'period' => $period,
        'top_users' => $topUsers
    ]);
}

/**
 * Statistics by location (province)
 */
function getLocationStats() {
    $db = db();

    $fromDate = $_GET['from_date'] ?? date('Y-m-01');
    $toDate = $_GET['to_date'] ?? date('Y-m-d');

    // Validate dates if provided by user
    if (isset($_GET['from_date']) && !isValidDate($fromDate)) {
        errorResponse('Invalid from_date format. Expected: YYYY-MM-DD', 400);
    }
    if (isset($_GET['to_date']) && !isValidDate($toDate)) {
        errorResponse('Invalid to_date format. Expected: YYYY-MM-DD', 400);
    }

    $stats = $db->fetchAll(
        "SELECT
            p.id,
            p.name as province_name,
            COUNT(DISTINCT u.id) as total_users,
            COUNT(o.id) as total_orders,
            SUM(CASE WHEN o.status = 'approved' THEN 1 ELSE 0 END) as approved_orders,
            SUM(CASE WHEN o.status = 'approved' THEN o.total_revenue ELSE 0 END) as total_revenue,
            SUM(CASE WHEN o.status = 'approved' THEN o.profit ELSE 0 END) as total_profit
         FROM provinces p
         LEFT JOIN users u ON p.id = u.province_id
         LEFT JOIN orders o ON u.id = o.user_id AND DATE(o.created_at) BETWEEN ? AND ?
         GROUP BY p.id
         HAVING total_orders > 0
         ORDER BY total_revenue DESC",
        [$fromDate, $toDate]
    );

    successResponse('', [
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'locations' => $stats
    ]);
}
