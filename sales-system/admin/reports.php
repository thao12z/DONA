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
    <title>Báo cáo kinh doanh - DONA Paper Admin</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .report-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .report-filters > * {
            flex: 1;
            min-width: 150px;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .metric-card h4 {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        .metric-card .value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
        }
        .metric-card .change {
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .metric-card .change.positive {
            color: #28a745;
        }
        .metric-card .change.negative {
            color: #dc3545;
        }
        .report-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #dee2e6;
        }
        .report-tab {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.2s;
        }
        .report-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .report-tab:hover {
            color: var(--primary);
        }
        .report-section {
            display: none;
        }
        .report-section.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-logo">DONA PAPER</div>
            <ul class="admin-sidebar-menu">
                <li><a href="/admin/index.php">Dashboard</a></li>
                <li><a href="/admin/orders.php">Quản lý đơn hàng</a></li>
                <li><a href="/admin/users.php">Quản lý nhân viên</a></li>
                <li><a href="/admin/reports.php" class="active">Báo cáo</a></li>
                <li><a href="/admin/logout.php">Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Báo cáo kinh doanh</h1>
                <div>Xin chào, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></div>
            </div>

            <!-- Report Filters -->
            <div class="card">
                <div class="report-filters">
                    <div>
                        <label class="form-label">Từ ngày</label>
                        <input type="date" id="filter-from-date" class="form-control">
                    </div>
                    <div>
                        <label class="form-label">Đến ngày</label>
                        <input type="date" id="filter-to-date" class="form-control">
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button class="btn btn-primary" onclick="applyFilters()">Áp dụng</button>
                        <button class="btn btn-secondary" onclick="resetFilters()" style="margin-left: 0.5rem;">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div id="key-metrics" class="metrics-grid">
                <div class="metric-card">
                    <h4>Tổng doanh thu</h4>
                    <div class="value" id="metric-revenue">-</div>
                    <div class="change" id="metric-revenue-change"></div>
                </div>
                <div class="metric-card">
                    <h4>Tổng lợi nhuận</h4>
                    <div class="value" id="metric-profit">-</div>
                    <div class="change" id="metric-profit-change"></div>
                </div>
                <div class="metric-card">
                    <h4>Đơn hàng</h4>
                    <div class="value" id="metric-orders">-</div>
                    <div class="change" id="metric-orders-change"></div>
                </div>
                <div class="metric-card">
                    <h4>Tỷ lệ duyệt</h4>
                    <div class="value" id="metric-approval-rate">-</div>
                    <div class="change" id="metric-approval-change"></div>
                </div>
            </div>

            <!-- Report Tabs -->
            <div class="card">
                <div class="report-tabs">
                    <button class="report-tab active" onclick="switchTab('overview')">Tổng quan</button>
                    <button class="report-tab" onclick="switchTab('user-performance')">Hiệu suất nhân viên</button>
                    <button class="report-tab" onclick="switchTab('location')">Theo khu vực</button>
                    <button class="report-tab" onclick="switchTab('trend')">Xu hướng</button>
                </div>

                <!-- Overview Tab -->
                <div id="tab-overview" class="report-section active">
                    <h3>Biểu đồ doanh thu và lợi nhuận</h3>
                    <div class="chart-container">
                        <canvas id="revenue-chart"></canvas>
                    </div>

                    <h3 style="margin-top: 2rem;">Phân bố trạng thái đơn hàng</h3>
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="status-chart"></canvas>
                    </div>
                </div>

                <!-- User Performance Tab -->
                <div id="tab-user-performance" class="report-section">
                    <h3>Hiệu suất theo nhân viên</h3>
                    <div id="user-performance-container">
                        <div class="loading">
                            <div class="spinner"></div>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                </div>

                <!-- Location Tab -->
                <div id="tab-location" class="report-section">
                    <h3>Chỉ số theo khu vực</h3>
                    <div class="chart-container">
                        <canvas id="location-chart"></canvas>
                    </div>
                    <div id="location-stats-container" style="margin-top: 2rem;"></div>
                </div>

                <!-- Trend Tab -->
                <div id="tab-trend" class="report-section">
                    <h3>Xu hướng đơn hàng theo thời gian</h3>
                    <div class="chart-container">
                        <canvas id="trend-chart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        let charts = {};
        let currentFilters = {};

        // Initialize
        function init() {
            // Set default date range (last 30 days)
            const toDate = new Date();
            const fromDate = new Date();
            fromDate.setDate(fromDate.getDate() - 30);

            document.getElementById('filter-from-date').value = fromDate.toISOString().split('T')[0];
            document.getElementById('filter-to-date').value = toDate.toISOString().split('T')[0];

            loadAllReports();
        }

        // Apply filters
        function applyFilters() {
            currentFilters = {
                from_date: document.getElementById('filter-from-date').value,
                to_date: document.getElementById('filter-to-date').value
            };
            loadAllReports();
        }

        // Reset filters
        function resetFilters() {
            const toDate = new Date();
            const fromDate = new Date();
            fromDate.setDate(fromDate.getDate() - 30);

            document.getElementById('filter-from-date').value = fromDate.toISOString().split('T')[0];
            document.getElementById('filter-to-date').value = toDate.toISOString().split('T')[0];

            currentFilters = {};
            loadAllReports();
        }

        // Load all reports
        async function loadAllReports() {
            await Promise.all([
                loadKeyMetrics(),
                loadRevenueChart(),
                loadStatusChart(),
                loadUserPerformance(),
                loadLocationStats(),
                loadTrendChart()
            ]);
        }

        // Load key metrics
        async function loadKeyMetrics() {
            try {
                const params = new URLSearchParams(currentFilters);
                const response = await App.get(`reports.php?action=revenue&${params.toString()}`);

                if (response.success) {
                    const data = response.data;

                    document.getElementById('metric-revenue').textContent = App.formatCurrency(data.total_revenue || 0);
                    document.getElementById('metric-profit').textContent = App.formatCurrency(data.total_profit || 0);
                    document.getElementById('metric-orders').textContent = data.total_orders || 0;

                    const approvalRate = data.total_orders > 0
                        ? ((data.approved_orders / data.total_orders) * 100).toFixed(1)
                        : 0;
                    document.getElementById('metric-approval-rate').textContent = approvalRate + '%';
                }
            } catch (error) {
                console.error('Failed to load key metrics:', error);
            }
        }

        // Load revenue chart
        async function loadRevenueChart() {
            try {
                const params = new URLSearchParams(currentFilters);
                const response = await App.get(`reports.php?action=revenue&${params.toString()}&group_by=date`);

                if (response.success && response.data.daily) {
                    const dailyData = response.data.daily;
                    const labels = dailyData.map(d => new Date(d.date).toLocaleDateString('vi-VN'));
                    const revenues = dailyData.map(d => d.revenue);
                    const profits = dailyData.map(d => d.profit);

                    if (charts.revenue) charts.revenue.destroy();

                    const ctx = document.getElementById('revenue-chart').getContext('2d');
                    charts.revenue = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Doanh thu',
                                    data: revenues,
                                    borderColor: '#1b76ff',
                                    backgroundColor: 'rgba(27, 118, 255, 0.1)',
                                    tension: 0.4
                                },
                                {
                                    label: 'Lợi nhuận',
                                    data: profits,
                                    borderColor: '#28a745',
                                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                    tension: 0.4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return App.formatCurrency(value);
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Failed to load revenue chart:', error);
            }
        }

        // Load status chart
        async function loadStatusChart() {
            try {
                const params = new URLSearchParams(currentFilters);
                const response = await App.get(`reports.php?action=dashboard&${params.toString()}`);

                if (response.success) {
                    const data = response.data;

                    if (charts.status) charts.status.destroy();

                    const ctx = document.getElementById('status-chart').getContext('2d');
                    charts.status = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Chờ duyệt', 'Đã duyệt', 'Từ chối'],
                            datasets: [{
                                data: [
                                    data.orders_pending || 0,
                                    data.orders_approved || 0,
                                    data.orders_rejected || 0
                                ],
                                backgroundColor: ['#ffc107', '#28a745', '#dc3545']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Failed to load status chart:', error);
            }
        }

        // Load user performance
        async function loadUserPerformance() {
            const container = document.getElementById('user-performance-container');

            try {
                const params = new URLSearchParams(currentFilters);
                const response = await App.get(`reports.php?action=user-performance&${params.toString()}`);

                if (response.success) {
                    const users = response.data;

                    if (users.length === 0) {
                        container.innerHTML = '<p class="text-center text-muted p-4">Không có dữ liệu</p>';
                        return;
                    }

                    let html = `
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Nhân viên</th>
                                        <th>Tổng đơn</th>
                                        <th>Đã duyệt</th>
                                        <th>Từ chối</th>
                                        <th>Tỷ lệ duyệt</th>
                                        <th>Doanh thu</th>
                                        <th>Lợi nhuận</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    users.forEach((user, index) => {
                        const approvalRate = user.total_orders > 0
                            ? ((user.approved_orders / user.total_orders) * 100).toFixed(1)
                            : 0;

                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td><strong>${user.full_name}</strong></td>
                                <td>${user.total_orders}</td>
                                <td class="text-success">${user.approved_orders}</td>
                                <td class="text-danger">${user.rejected_orders}</td>
                                <td>${approvalRate}%</td>
                                <td>${App.formatCurrency(user.total_revenue || 0)}</td>
                                <td class="${user.total_profit > 0 ? 'text-success' : 'text-danger'}">
                                    ${App.formatCurrency(user.total_profit || 0)}
                                </td>
                            </tr>
                        `;
                    });

                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                }
            } catch (error) {
                container.innerHTML = '<p class="text-center text-danger p-4">Không thể tải dữ liệu</p>';
            }
        }

        // Load location stats
        async function loadLocationStats() {
            try {
                const params = new URLSearchParams(currentFilters);
                const response = await App.get(`reports.php?action=location-stats&${params.toString()}`);

                if (response.success) {
                    const locations = response.data;

                    if (locations.length > 0) {
                        // Chart
                        const labels = locations.map(l => l.province_name);
                        const orders = locations.map(l => l.total_orders);
                        const revenues = locations.map(l => l.total_revenue);

                        if (charts.location) charts.location.destroy();

                        const ctx = document.getElementById('location-chart').getContext('2d');
                        charts.location = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: 'Số đơn hàng',
                                        data: orders,
                                        backgroundColor: 'rgba(27, 118, 255, 0.7)',
                                        yAxisID: 'y'
                                    },
                                    {
                                        label: 'Doanh thu',
                                        data: revenues,
                                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                                        yAxisID: 'y1'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'top'
                                    }
                                },
                                scales: {
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Số đơn hàng'
                                        }
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        beginAtZero: true,
                                        title: {
                                            display: true,
                                            text: 'Doanh thu (VNĐ)'
                                        },
                                        grid: {
                                            drawOnChartArea: false
                                        }
                                    }
                                }
                            }
                        });

                        // Table
                        let html = `
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Tỉnh/Thành phố</th>
                                            <th>Số đơn</th>
                                            <th>Doanh thu</th>
                                            <th>Lợi nhuận</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        locations.forEach(location => {
                            html += `
                                <tr>
                                    <td><strong>${location.province_name}</strong></td>
                                    <td>${location.total_orders}</td>
                                    <td>${App.formatCurrency(location.total_revenue || 0)}</td>
                                    <td class="${location.total_profit > 0 ? 'text-success' : 'text-danger'}">
                                        ${App.formatCurrency(location.total_profit || 0)}
                                    </td>
                                </tr>
                            `;
                        });

                        html += '</tbody></table></div>';
                        document.getElementById('location-stats-container').innerHTML = html;
                    }
                }
            } catch (error) {
                console.error('Failed to load location stats:', error);
            }
        }

        // Load trend chart
        async function loadTrendChart() {
            try {
                const params = new URLSearchParams(currentFilters);
                const response = await App.get(`reports.php?action=orders-trend&${params.toString()}`);

                if (response.success && response.data.trend) {
                    const trendData = response.data.trend;
                    const labels = trendData.map(d => new Date(d.date).toLocaleDateString('vi-VN'));
                    const pending = trendData.map(d => d.pending);
                    const approved = trendData.map(d => d.approved);
                    const rejected = trendData.map(d => d.rejected);

                    if (charts.trend) charts.trend.destroy();

                    const ctx = document.getElementById('trend-chart').getContext('2d');
                    charts.trend = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Chờ duyệt',
                                    data: pending,
                                    borderColor: '#ffc107',
                                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                                    tension: 0.4
                                },
                                {
                                    label: 'Đã duyệt',
                                    data: approved,
                                    borderColor: '#28a745',
                                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                    tension: 0.4
                                },
                                {
                                    label: 'Từ chối',
                                    data: rejected,
                                    borderColor: '#dc3545',
                                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                    tension: 0.4
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Failed to load trend chart:', error);
            }
        }

        // Switch tabs
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.report-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Update sections
            document.querySelectorAll('.report-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(`tab-${tabName}`).classList.add('active');
        }

        // Initialize on page load
        init();
    </script>
</body>
</html>
