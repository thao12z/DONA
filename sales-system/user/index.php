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
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Nhập đơn hàng - DONA Paper</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body>
    <div class="container">
        <div class="user-header">
            <h1 class="user-welcome">Xin chào, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <div class="user-info">
                <p>Chức danh: <?php echo htmlspecialchars($user['position'] ?? 'Nhân viên kinh doanh'); ?></p>
                <div style="margin-top: 1rem;">
                    <a href="/user/history.php" class="btn btn-outline" style="color: white; border-color: white;">Lịch sử đơn hàng</a>
                    <a href="/user/logout.php" class="btn btn-outline" style="color: white; border-color: white;">Đăng xuất</a>
                </div>
            </div>
        </div>

        <div class="order-form-card">
            <h2>Nhập đơn hàng mới</h2>

            <form id="order-form">
                <!-- Thông tin khách hàng -->
                <div class="form-section">
                    <h3 class="form-section-title">Thông tin khách hàng</h3>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label form-label-required">Tên khách hàng</label>
                                <input type="text" name="customer_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label form-label-required">Số điện thoại</label>
                                <input type="tel" name="customer_phone" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">Địa chỉ</label>
                        <textarea name="customer_address" class="form-control" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Mạng xã hội</label>
                                <input type="text" name="customer_social" class="form-control" placeholder="Facebook/Zalo/Instagram">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label form-label-required">Loại khách hàng</label>
                                <select name="customer_type" class="form-control" required>
                                    <option value="">-- Chọn loại --</option>
                                    <option value="Lẻ">Lẻ</option>
                                    <option value="Sỉ">Sỉ</option>
                                    <option value="Đại lý">Đại lý</option>
                                    <option value="Khác">Khác</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Vị trí GPS</label>
                        <div class="gps-picker">
                            <input type="text" id="gps-display" class="form-control" readonly placeholder="Nhấn nút để lấy vị trí">
                            <button type="button" class="btn btn-secondary" onclick="getLocation()">Lấy vị trí</button>
                        </div>
                        <input type="hidden" name="customer_latitude" id="latitude">
                        <input type="hidden" name="customer_longitude" id="longitude">
                    </div>
                </div>

                <!-- Thông tin đơn hàng -->
                <div class="form-section">
                    <h3 class="form-section-title">Thông tin đơn hàng</h3>

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label form-label-required">Giá nhập (VNĐ)</label>
                                <input type="number" name="purchase_price" class="form-control" required min="0" step="1000" onchange="calculateProfit()">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label form-label-required">Số lượng</label>
                                <input type="number" name="quantity" class="form-control" required min="1" onchange="calculateProfit()">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label form-label-required">Tổng thu (VNĐ)</label>
                                <input type="number" name="total_revenue" class="form-control" required min="0" step="1000" onchange="calculateProfit()">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Chi phí phát sinh (VNĐ)</label>
                                <input type="number" name="other_costs" class="form-control" value="0" min="0" step="1000" onchange="calculateProfit()">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Lợi nhuận dự kiến</label>
                                <input type="text" id="profit-display" class="form-control" readonly value="0 đ">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="text-right">
                    <button type="reset" class="btn btn-secondary">Nhập lại</button>
                    <button type="submit" class="btn btn-primary btn-lg">Gửi đơn hàng</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        // Get GPS location
        async function getLocation() {
            try {
                const position = await App.getGeolocation();
                document.getElementById('latitude').value = position.latitude;
                document.getElementById('longitude').value = position.longitude;
                document.getElementById('gps-display').value = `${position.latitude.toFixed(6)}, ${position.longitude.toFixed(6)}`;
                App.showSuccess('Đã lấy vị trí thành công!');
            } catch (error) {
                App.showError('Không thể lấy vị trí. Vui lòng cho phép truy cập vị trí.');
            }
        }

        // Calculate profit
        function calculateProfit() {
            const purchasePrice = parseFloat(document.querySelector('[name="purchase_price"]').value) || 0;
            const totalRevenue = parseFloat(document.querySelector('[name="total_revenue"]').value) || 0;
            const otherCosts = parseFloat(document.querySelector('[name="other_costs"]').value) || 0;

            const profit = totalRevenue - purchasePrice - otherCosts;
            document.getElementById('profit-display').value = App.formatCurrency(profit);
        }

        // Handle form submission
        document.getElementById('order-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!App.validateForm(e.target)) {
                return;
            }

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');

            const orderData = {
                customer_name: formData.get('customer_name'),
                customer_phone: formData.get('customer_phone'),
                customer_address: formData.get('customer_address'),
                customer_social: formData.get('customer_social'),
                customer_latitude: parseFloat(formData.get('customer_latitude')) || null,
                customer_longitude: parseFloat(formData.get('customer_longitude')) || null,
                customer_type: formData.get('customer_type'),
                purchase_price: parseFloat(formData.get('purchase_price')),
                quantity: parseInt(formData.get('quantity')),
                total_revenue: parseFloat(formData.get('total_revenue')),
                other_costs: parseFloat(formData.get('other_costs')) || 0,
                notes: formData.get('notes')
            };

            App.showLoading(submitBtn);

            try {
                const response = await App.post('orders.php', orderData);

                if (response.success) {
                    App.showSuccess('Tạo đơn hàng thành công!');
                    e.target.reset();
                    setTimeout(() => {
                        window.location.href = '/user/history.php';
                    }, 1500);
                }
            } catch (error) {
                // Error already shown by App.post
            } finally {
                App.hideLoading(submitBtn);
            }
        });
    </script>
</body>
</html>
