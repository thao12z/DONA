<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth()->requireLogin();
$user = auth()->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng - DONA Paper</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body>
    <div class="container">
        <div class="user-header">
            <h1>Lịch sử đơn hàng</h1>
            <div>
                <a href="/user/index.php" class="btn btn-outline" style="color: white; border-color: white;">Nhập đơn mới</a>
                <a href="/user/logout.php" class="btn btn-outline" style="color: white; border-color: white;">Đăng xuất</a>
            </div>
        </div>

        <div class="card">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-4">
                    <select id="status-filter" class="form-control">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending">Chờ duyệt</option>
                        <option value="approved">Đã duyệt</option>
                        <option value="rejected">Từ chối</option>
                    </select>
                </div>
                <div class="col-4">
                    <input type="date" id="from-date" class="form-control" placeholder="Từ ngày">
                </div>
                <div class="col-4">
                    <input type="date" id="to-date" class="form-control" placeholder="Đến ngày">
                </div>
            </div>

            <div id="orders-container">
                <div class="loading"><div class="spinner"></div><p>Đang tải...</p></div>
            </div>

            <div id="pagination" class="text-center mt-3"></div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        let currentPage = 1;

        async function loadOrders(page = 1) {
            const status = document.getElementById('status-filter').value;
            const fromDate = document.getElementById('from-date').value;
            const toDate = document.getElementById('to-date').value;

            let url = `orders.php?page=${page}`;
            if (status) url += `&status=${status}`;
            if (fromDate) url += `&from_date=${fromDate}`;
            if (toDate) url += `&to_date=${toDate}`;

            try {
                const response = await App.get(url);

                if (response.success) {
                    displayOrders(response.data.orders);
                    displayPagination(response.data.pagination);
                }
            } catch (error) {
                document.getElementById('orders-container').innerHTML =
                    '<div class="alert alert-danger">Không thể tải dữ liệu</div>';
            }
        }

        function displayOrders(orders) {
            const container = document.getElementById('orders-container');

            if (orders.length === 0) {
                container.innerHTML = '<div class="text-center text-muted p-4">Chưa có đơn hàng nào</div>';
                return;
            }

            container.innerHTML = orders.map(order => `
                <div class="order-history-item status-${order.status}">
                    <div class="order-history-header">
                        <div>
                            <strong>${order.customer_name}</strong>
                            <span class="badge badge-${getBadgeClass(order.status)}">${getStatusText(order.status)}</span>
                        </div>
                        <div class="text-muted">${App.formatDate(order.created_at)}</div>
                    </div>
                    <div class="order-history-details">
                        <p>Điện thoại: ${order.customer_phone}</p>
                        <p>Tổng thu: <strong>${App.formatCurrency(order.total_revenue)}</strong></p>
                        <p>Lợi nhuận: <strong>${App.formatCurrency(order.profit)}</strong></p>
                        ${order.admin_message ? `<div class="alert alert-warning mt-2"><strong>Lời nhắn:</strong> ${order.admin_message}</div>` : ''}
                    </div>
                </div>
            `).join('');
        }

        function displayPagination(pagination) {
            const container = document.getElementById('pagination');
            if (pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            container.innerHTML = `
                <button class="btn btn-sm" ${!pagination.has_prev ? 'disabled' : ''} onclick="loadOrders(${pagination.current_page - 1})">Trước</button>
                <span class="mx-2">Trang ${pagination.current_page}/${pagination.total_pages}</span>
                <button class="btn btn-sm" ${!pagination.has_next ? 'disabled' : ''} onclick="loadOrders(${pagination.current_page + 1})">Sau</button>
            `;
        }

        function getBadgeClass(status) {
            return {pending: 'warning', approved: 'success', rejected: 'danger'}[status] || 'secondary';
        }

        function getStatusText(status) {
            return {pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Từ chối'}[status] || status;
        }

        // Event listeners
        document.getElementById('status-filter').addEventListener('change', () => loadOrders(1));
        document.getElementById('from-date').addEventListener('change', () => loadOrders(1));
        document.getElementById('to-date').addEventListener('change', () => loadOrders(1));

        // Initial load
        loadOrders();
    </script>
</body>
</html>
