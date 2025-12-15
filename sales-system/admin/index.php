<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth()->requireLogin(true);
$user = auth()->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DONA Paper Admin</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-logo">DONA PAPER</div>
            <ul class="admin-sidebar-menu">
                <li><a href="/admin/index.php" class="active">Dashboard</a></li>
                <li><a href="/admin/orders.php">Quản lý đơn hàng</a></li>
                <li><a href="/admin/users.php">Quản lý nhân viên</a></li>
                <li><a href="/admin/reports.php">Báo cáo</a></li>
                <li><a href="/admin/logout.php">Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <div>Xin chào, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></div>
            </div>

            <div id="stats" class="stats-grid">
                <div class="stat-card">
                    <h3 id="pending-orders">-</h3>
                    <p>Đơn chờ duyệt</p>
                </div>
                <div class="stat-card">
                    <h3 id="today-orders">-</h3>
                    <p>Đơn hàng hôm nay</p>
                </div>
                <div class="stat-card">
                    <h3 id="today-revenue">-</h3>
                    <p>Doanh thu hôm nay</p>
                </div>
                <div class="stat-card">
                    <h3 id="today-profit">-</h3>
                    <p>Lợi nhuận hôm nay</p>
                </div>
            </div>

            <div class="card">
                <h2>Đơn hàng chờ duyệt</h2>
                <div id="pending-orders-list">
                    <div class="loading"><div class="spinner"></div><p>Đang tải...</p></div>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        async function loadDashboard() {
            try {
                const [stats, orders] = await Promise.all([
                    App.get('reports.php?action=dashboard'),
                    App.get('orders.php?status=pending&limit=10')
                ]);

                if (stats.success) {
                    document.getElementById('pending-orders').textContent = stats.data.orders_pending;
                    document.getElementById('today-orders').textContent = stats.data.orders_today;
                    document.getElementById('today-revenue').textContent = App.formatCurrency(stats.data.revenue_today);
                    document.getElementById('today-profit').textContent = App.formatCurrency(stats.data.profit_today);
                }

                if (orders.success) {
                    displayPendingOrders(orders.data.orders);
                }
            } catch (error) {
                App.showError('Không thể tải dữ liệu dashboard');
            }
        }

        function displayPendingOrders(orders) {
            const container = document.getElementById('pending-orders-list');

            if (orders.length === 0) {
                container.innerHTML = '<p class="text-center text-muted p-4">Không có đơn hàng chờ duyệt</p>';
                return;
            }

            container.innerHTML = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Khách hàng</th>
                                <th>Điện thoại</th>
                                <th>Tổng thu</th>
                                <th>Lợi nhuận</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${orders.map(order => `
                                <tr>
                                    <td>${order.user_name || '-'}</td>
                                    <td><strong>${order.customer_name}</strong></td>
                                    <td>${order.customer_phone}</td>
                                    <td>${App.formatCurrency(order.total_revenue)}</td>
                                    <td class="${order.profit > 0 ? 'text-success' : 'text-danger'}">${App.formatCurrency(order.profit)}</td>
                                    <td>${App.formatDate(order.created_at)}</td>
                                    <td>
                                        <button class="btn btn-sm btn-success" onclick="approveOrder(${order.id})">Duyệt</button>
                                        <button class="btn btn-sm btn-danger" onclick="rejectOrder(${order.id})">Từ chối</button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        async function approveOrder(id) {
            if (!App.confirm('Duyệt đơn hàng này?')) return;

            try {
                await App.put('orders.php', { id, status: 'approved' });
                App.showSuccess('Đã duyệt đơn hàng');
                loadDashboard();
            } catch (error) {
                // Error shown by App.put
            }
        }

        async function rejectOrder(id) {
            const message = prompt('Lý do từ chối:');
            if (!message) return;

            try {
                await App.put('orders.php', { id, status: 'rejected', admin_message: message });
                App.showSuccess('Đã từ chối đơn hàng');
                loadDashboard();
            } catch (error) {
                // Error shown by App.put
            }
        }

        loadDashboard();
        setInterval(loadDashboard, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
