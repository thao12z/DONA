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
    <title>Quản lý đơn hàng - DONA Paper Admin</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .bulk-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .bulk-actions-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .filters > * {
            flex: 1;
            min-width: 150px;
        }
        .order-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .order-row:hover {
            background-color: #f8f9fa;
        }
        .order-row.selected {
            background-color: #e7f3ff;
        }
        .order-details {
            display: none;
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        .order-details.show {
            display: block;
        }
        .order-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .order-detail-section h4 {
            margin-bottom: 0.5rem;
            color: var(--primary);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .order-detail-item {
            margin-bottom: 0.5rem;
        }
        .order-detail-item label {
            font-weight: 600;
            margin-right: 0.5rem;
        }
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        .checkbox-cell input[type="checkbox"] {
            cursor: pointer;
            width: 18px;
            height: 18px;
        }
        .expand-icon {
            display: inline-block;
            transition: transform 0.2s;
            margin-right: 0.5rem;
        }
        .expand-icon.expanded {
            transform: rotate(90deg);
        }
        .stats-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-item {
            flex: 1;
            padding: 1rem;
            background: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .stat-item .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        .stat-item .label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-logo">DONA PAPER</div>
            <ul class="admin-sidebar-menu">
                <li><a href="/admin/index.php">Dashboard</a></li>
                <li><a href="/admin/orders.php" class="active">Quản lý đơn hàng</a></li>
                <li><a href="/admin/users.php">Quản lý nhân viên</a></li>
                <li><a href="/admin/reports.php">Báo cáo</a></li>
                <li><a href="/admin/logout.php">Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Quản lý đơn hàng</h1>
                <div>Xin chào, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></div>
            </div>

            <!-- Statistics Bar -->
            <div id="stats-bar" class="stats-bar">
                <div class="stat-item">
                    <div class="value" id="stat-pending">-</div>
                    <div class="label">Chờ duyệt</div>
                </div>
                <div class="stat-item">
                    <div class="value" id="stat-approved">-</div>
                    <div class="label">Đã duyệt</div>
                </div>
                <div class="stat-item">
                    <div class="value" id="stat-rejected">-</div>
                    <div class="label">Từ chối</div>
                </div>
                <div class="stat-item">
                    <div class="value" id="stat-total">-</div>
                    <div class="label">Tổng cộng</div>
                </div>
            </div>

            <div class="card">
                <!-- Filters -->
                <div class="filters">
                    <div>
                        <label class="form-label">Trạng thái</label>
                        <select id="filter-status" class="form-control">
                            <option value="">Tất cả</option>
                            <option value="pending">Chờ duyệt</option>
                            <option value="approved">Đã duyệt</option>
                            <option value="rejected">Từ chối</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Từ ngày</label>
                        <input type="date" id="filter-from" class="form-control">
                    </div>
                    <div>
                        <label class="form-label">Đến ngày</label>
                        <input type="date" id="filter-to" class="form-control">
                    </div>
                    <div>
                        <label class="form-label">Tìm kiếm</label>
                        <input type="text" id="filter-search" class="form-control" placeholder="Tên, SĐT khách hàng...">
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button class="btn btn-primary" onclick="loadOrders()">Lọc</button>
                        <button class="btn btn-secondary" onclick="resetFilters()" style="margin-left: 0.5rem;">Reset</button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="bulk-actions">
                    <div>
                        <input type="checkbox" id="select-all" style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="select-all" style="margin-left: 0.5rem; cursor: pointer;">Chọn tất cả</label>
                    </div>
                    <div class="bulk-actions-buttons">
                        <button class="btn btn-sm btn-success" onclick="bulkApprove()" id="bulk-approve-btn">
                            Duyệt đã chọn (<span id="selected-count">0</span>)
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="bulkReject()" id="bulk-reject-btn">
                            Từ chối đã chọn (<span id="selected-count-2">0</span>)
                        </button>
                    </div>
                    <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                        <button class="btn btn-sm btn-outline" onclick="showImportModal()">Import CSV</button>
                        <button class="btn btn-sm btn-outline" onclick="exportOrders()">Export CSV</button>
                    </div>
                </div>

                <!-- Orders Table -->
                <div id="orders-container">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Đang tải...</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div id="pagination-container" style="margin-top: 1.5rem;"></div>
            </div>
        </main>
    </div>

    <!-- Import Modal -->
    <div id="import-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Import đơn hàng từ CSV</h3>
                <button class="modal-close" onclick="closeImportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                    <strong>Định dạng CSV:</strong>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                        Tên khách hàng, Điện thoại, Địa chỉ, Mạng xã hội, Loại KH, Giá nhập, Số lượng, Tổng thu, Chi phí khác, Ghi chú
                    </p>
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">
                        Chú ý: GPS location sẽ để trống khi import CSV. Các trường bắt buộc: Tên, Điện thoại, Địa chỉ, Loại KH, Giá nhập, Số lượng, Tổng thu.
                    </p>
                </div>

                <form id="import-form">
                    <div class="form-group">
                        <label class="form-label form-label-required">Chọn file CSV</label>
                        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                    </div>

                    <div id="import-result" style="margin-top: 1rem; display: none;"></div>

                    <div class="text-right" style="margin-top: 2rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Hủy</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        let currentPage = 1;
        let selectedOrders = new Set();

        // Load orders
        async function loadOrders(page = 1) {
            currentPage = page;
            const container = document.getElementById('orders-container');

            try {
                const params = new URLSearchParams({
                    page: page,
                    limit: 20
                });

                const status = document.getElementById('filter-status').value;
                if (status) params.append('status', status);

                const fromDate = document.getElementById('filter-from').value;
                if (fromDate) params.append('from_date', fromDate);

                const toDate = document.getElementById('filter-to').value;
                if (toDate) params.append('to_date', toDate);

                const search = document.getElementById('filter-search').value;
                if (search) params.append('search', search);

                const response = await App.get(`orders.php?${params.toString()}`);

                if (response.success) {
                    displayOrders(response.data.orders);
                    displayPagination(response.data.pagination);
                    updateSelectedCount();
                }
            } catch (error) {
                container.innerHTML = '<p class="text-center text-danger p-4">Không thể tải dữ liệu</p>';
            }
        }

        // Load statistics
        async function loadStats() {
            try {
                const [pending, approved, rejected] = await Promise.all([
                    App.get('orders.php?status=pending&limit=1'),
                    App.get('orders.php?status=approved&limit=1'),
                    App.get('orders.php?status=rejected&limit=1')
                ]);

                document.getElementById('stat-pending').textContent = pending.data.pagination.total || 0;
                document.getElementById('stat-approved').textContent = approved.data.pagination.total || 0;
                document.getElementById('stat-rejected').textContent = rejected.data.pagination.total || 0;

                const total = (pending.data.pagination.total || 0) +
                             (approved.data.pagination.total || 0) +
                             (rejected.data.pagination.total || 0);
                document.getElementById('stat-total').textContent = total;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }

        // Display orders
        function displayOrders(orders) {
            const container = document.getElementById('orders-container');

            if (orders.length === 0) {
                container.innerHTML = '<p class="text-center text-muted p-4">Không có đơn hàng nào</p>';
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell"></th>
                                <th>ID</th>
                                <th>Nhân viên</th>
                                <th>Khách hàng</th>
                                <th>Điện thoại</th>
                                <th>Tổng thu</th>
                                <th>Lợi nhuận</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            orders.forEach(order => {
                const profit = order.total_revenue - order.purchase_price - order.other_costs;
                const statusBadge = getStatusBadge(order.status);
                const isSelected = selectedOrders.has(order.id);

                html += `
                    <tr class="order-row ${isSelected ? 'selected' : ''}" data-order-id="${order.id}">
                        <td class="checkbox-cell" onclick="event.stopPropagation();">
                            <input type="checkbox" class="order-checkbox" value="${order.id}"
                                   ${isSelected ? 'checked' : ''} onchange="toggleOrderSelection(${order.id})">
                        </td>
                        <td onclick="toggleOrderDetails(${order.id})">
                            <span class="expand-icon" id="expand-${order.id}">▶</span>
                            #${order.id}
                        </td>
                        <td onclick="toggleOrderDetails(${order.id})">${order.user_name || '-'}</td>
                        <td onclick="toggleOrderDetails(${order.id})"><strong>${order.customer_name}</strong></td>
                        <td onclick="toggleOrderDetails(${order.id})">${order.customer_phone}</td>
                        <td onclick="toggleOrderDetails(${order.id})">${App.formatCurrency(order.total_revenue)}</td>
                        <td onclick="toggleOrderDetails(${order.id})" class="${profit > 0 ? 'text-success' : 'text-danger'}">
                            ${App.formatCurrency(profit)}
                        </td>
                        <td onclick="toggleOrderDetails(${order.id})">${statusBadge}</td>
                        <td onclick="toggleOrderDetails(${order.id})">${App.formatDate(order.created_at)}</td>
                        <td onclick="event.stopPropagation();">
                            ${order.status === 'pending' ? `
                                <button class="btn btn-sm btn-success" onclick="approveOrder(${order.id})">Duyệt</button>
                                <button class="btn btn-sm btn-danger" onclick="rejectOrder(${order.id})">Từ chối</button>
                            ` : '-'}
                        </td>
                    </tr>
                    <tr id="details-${order.id}" class="order-details-row">
                        <td colspan="10" class="order-details">
                            ${renderOrderDetails(order, profit)}
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        // Render order details
        function renderOrderDetails(order, profit) {
            return `
                <div class="order-details-grid">
                    <div class="order-detail-section">
                        <h4>Thông tin khách hàng</h4>
                        <div class="order-detail-item">
                            <label>Tên:</label>
                            <span>${order.customer_name}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Điện thoại:</label>
                            <span>${order.customer_phone}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Địa chỉ:</label>
                            <span>${order.customer_address}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Mạng xã hội:</label>
                            <span>${order.customer_social || '-'}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Loại KH:</label>
                            <span>${order.customer_type}</span>
                        </div>
                        ${order.customer_latitude && order.customer_longitude ? `
                        <div class="order-detail-item">
                            <label>GPS:</label>
                            <span>${order.customer_latitude.toFixed(6)}, ${order.customer_longitude.toFixed(6)}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div class="order-detail-section">
                        <h4>Thông tin đơn hàng</h4>
                        <div class="order-detail-item">
                            <label>Giá nhập:</label>
                            <span>${App.formatCurrency(order.purchase_price)}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Số lượng:</label>
                            <span>${order.quantity}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Tổng thu:</label>
                            <span>${App.formatCurrency(order.total_revenue)}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Chi phí phát sinh:</label>
                            <span>${App.formatCurrency(order.other_costs)}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Lợi nhuận:</label>
                            <span class="${profit > 0 ? 'text-success' : 'text-danger'}">${App.formatCurrency(profit)}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Ghi chú:</label>
                            <span>${order.notes || '-'}</span>
                        </div>
                    </div>

                    <div class="order-detail-section">
                        <h4>Thông tin hệ thống</h4>
                        <div class="order-detail-item">
                            <label>Nhân viên:</label>
                            <span>${order.user_name || '-'} ${order.user_position ? `(${order.user_position})` : ''}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Trạng thái:</label>
                            <span>${getStatusText(order.status)}</span>
                        </div>
                        <div class="order-detail-item">
                            <label>Ngày tạo:</label>
                            <span>${App.formatDate(order.created_at)}</span>
                        </div>
                        ${order.admin_message ? `
                        <div class="order-detail-item">
                            <label>Tin nhắn admin:</label>
                            <span class="text-danger">${order.admin_message}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        // Toggle order details
        function toggleOrderDetails(orderId) {
            const detailsRow = document.getElementById(`details-${orderId}`);
            const details = detailsRow.querySelector('.order-details');
            const expandIcon = document.getElementById(`expand-${orderId}`);

            details.classList.toggle('show');
            expandIcon.classList.toggle('expanded');
        }

        // Toggle order selection
        function toggleOrderSelection(orderId) {
            if (selectedOrders.has(orderId)) {
                selectedOrders.delete(orderId);
            } else {
                selectedOrders.add(orderId);
            }
            updateSelectedCount();
            updateRowSelection(orderId);
        }

        // Update row selection visual
        function updateRowSelection(orderId) {
            const row = document.querySelector(`.order-row[data-order-id="${orderId}"]`);
            if (row) {
                if (selectedOrders.has(orderId)) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            }
        }

        // Update selected count
        function updateSelectedCount() {
            const count = selectedOrders.size;
            document.getElementById('selected-count').textContent = count;
            document.getElementById('selected-count-2').textContent = count;

            const selectAllCheckbox = document.getElementById('select-all');
            const allCheckboxes = document.querySelectorAll('.order-checkbox');
            selectAllCheckbox.checked = allCheckboxes.length > 0 && count === allCheckboxes.length;
        }

        // Select all orders
        document.getElementById('select-all').addEventListener('change', (e) => {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            if (e.target.checked) {
                checkboxes.forEach(cb => {
                    selectedOrders.add(parseInt(cb.value));
                    cb.checked = true;
                });
            } else {
                selectedOrders.clear();
                checkboxes.forEach(cb => cb.checked = false);
            }
            updateSelectedCount();
            document.querySelectorAll('.order-row').forEach(row => {
                const orderId = parseInt(row.dataset.orderId);
                if (selectedOrders.has(orderId)) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
        });

        // Bulk approve
        async function bulkApprove() {
            if (selectedOrders.size === 0) {
                App.showError('Vui lòng chọn ít nhất một đơn hàng');
                return;
            }

            if (!App.confirm(`Duyệt ${selectedOrders.size} đơn hàng đã chọn?`)) {
                return;
            }

            const button = document.getElementById('bulk-approve-btn');
            App.showLoading(button);

            try {
                const promises = Array.from(selectedOrders).map(orderId =>
                    App.put('orders.php', { id: orderId, status: 'approved' })
                );

                await Promise.all(promises);

                App.showSuccess(`Đã duyệt ${selectedOrders.size} đơn hàng`);
                selectedOrders.clear();
                loadOrders(currentPage);
                loadStats();
            } catch (error) {
                // Error already shown
            } finally {
                App.hideLoading(button);
            }
        }

        // Bulk reject
        async function bulkReject() {
            if (selectedOrders.size === 0) {
                App.showError('Vui lòng chọn ít nhất một đơn hàng');
                return;
            }

            const message = prompt('Lý do từ chối (áp dụng cho tất cả):');
            if (!message) return;

            const button = document.getElementById('bulk-reject-btn');
            App.showLoading(button);

            try {
                const promises = Array.from(selectedOrders).map(orderId =>
                    App.put('orders.php', { id: orderId, status: 'rejected', admin_message: message })
                );

                await Promise.all(promises);

                App.showSuccess(`Đã từ chối ${selectedOrders.size} đơn hàng`);
                selectedOrders.clear();
                loadOrders(currentPage);
                loadStats();
            } catch (error) {
                // Error already shown
            } finally {
                App.hideLoading(button);
            }
        }

        // Approve single order
        async function approveOrder(id) {
            if (!App.confirm('Duyệt đơn hàng này?')) return;

            try {
                await App.put('orders.php', { id, status: 'approved' });
                App.showSuccess('Đã duyệt đơn hàng');
                loadOrders(currentPage);
                loadStats();
            } catch (error) {
                // Error shown by App.put
            }
        }

        // Reject single order
        async function rejectOrder(id) {
            const message = prompt('Lý do từ chối:');
            if (!message) return;

            try {
                await App.put('orders.php', { id, status: 'rejected', admin_message: message });
                App.showSuccess('Đã từ chối đơn hàng');
                loadOrders(currentPage);
                loadStats();
            } catch (error) {
                // Error shown by App.put
            }
        }

        // Export orders
        async function exportOrders() {
            const params = new URLSearchParams();

            const status = document.getElementById('filter-status').value;
            if (status) params.append('status', status);

            const fromDate = document.getElementById('filter-from').value;
            if (fromDate) params.append('from_date', fromDate);

            const toDate = document.getElementById('filter-to').value;
            if (toDate) params.append('to_date', toDate);

            const url = `/api/orders.php?action=export&${params.toString()}`;
            window.open(url, '_blank');
        }

        // Display pagination
        function displayPagination(pagination) {
            const container = document.getElementById('pagination-container');

            if (pagination.total_pages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '<div class="pagination">';

            if (pagination.current_page > 1) {
                html += `<button class="btn btn-sm btn-outline" onclick="loadOrders(${pagination.current_page - 1})">« Trước</button>`;
            }

            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    html += `<button class="btn btn-sm btn-primary">${i}</button>`;
                } else if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                    html += `<button class="btn btn-sm btn-outline" onclick="loadOrders(${i})">${i}</button>`;
                } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                    html += '<span>...</span>';
                }
            }

            if (pagination.current_page < pagination.total_pages) {
                html += `<button class="btn btn-sm btn-outline" onclick="loadOrders(${pagination.current_page + 1})">Sau »</button>`;
            }

            html += '</div>';
            container.innerHTML = html;
        }

        // Get status badge
        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge badge-warning">Chờ duyệt</span>',
                'approved': '<span class="badge badge-success">Đã duyệt</span>',
                'rejected': '<span class="badge badge-danger">Từ chối</span>'
            };
            return badges[status] || status;
        }

        // Get status text
        function getStatusText(status) {
            const texts = {
                'pending': 'Chờ duyệt',
                'approved': 'Đã duyệt',
                'rejected': 'Từ chối'
            };
            return texts[status] || status;
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('filter-status').value = '';
            document.getElementById('filter-from').value = '';
            document.getElementById('filter-to').value = '';
            document.getElementById('filter-search').value = '';
            loadOrders(1);
        }

        // Show import modal
        function showImportModal() {
            document.getElementById('import-modal').style.display = 'flex';
            document.getElementById('import-form').reset();
            document.getElementById('import-result').style.display = 'none';
        }

        // Close import modal
        function closeImportModal() {
            document.getElementById('import-modal').style.display = 'none';
        }

        // Handle import form
        document.getElementById('import-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const resultDiv = document.getElementById('import-result');

            App.showLoading(submitBtn);
            resultDiv.style.display = 'none';

            try {
                const response = await App.postFormData('orders.php?action=import', formData);

                if (response.success) {
                    const data = response.data;
                    let html = `<div class="alert alert-success">
                        <strong>Import thành công!</strong><br>
                        Đã import ${data.imported} đơn hàng từ ${data.total_rows} dòng.
                    </div>`;

                    if (data.errors && data.errors.length > 0) {
                        html += `<div class="alert alert-warning" style="margin-top: 1rem;">
                            <strong>Lỗi (${data.errors.length}):</strong><br>
                            <ul style="margin: 0.5rem 0 0 1.5rem;">
                                ${data.errors.slice(0, 10).map(err => `<li>${err}</li>`).join('')}
                                ${data.errors.length > 10 ? `<li>... và ${data.errors.length - 10} lỗi khác</li>` : ''}
                            </ul>
                        </div>`;
                    }

                    resultDiv.innerHTML = html;
                    resultDiv.style.display = 'block';

                    setTimeout(() => {
                        closeImportModal();
                        loadOrders(currentPage);
                        loadStats();
                    }, 3000);
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    ${error.message || 'Không thể import file CSV'}
                </div>`;
                resultDiv.style.display = 'block';
            } finally {
                App.hideLoading(submitBtn);
            }
        });

        // Close modal on outside click
        document.getElementById('import-modal').addEventListener('click', (e) => {
            if (e.target.id === 'import-modal') {
                closeImportModal();
            }
        });

        // Initialize
        loadOrders();
        loadStats();
        setInterval(() => {
            loadOrders(currentPage);
            loadStats();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
