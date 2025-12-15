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
    <title>Quản lý nhân viên - DONA Paper Admin</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .id-card-preview {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .id-card-preview img {
            max-width: 200px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 4px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .upload-area:hover {
            border-color: var(--primary);
            background: #f8f9fa;
        }
        .upload-area input {
            display: none;
        }
        .stats-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .stats-badge.good {
            background: #d4edda;
            color: #155724;
        }
        .stats-badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        .stats-badge.danger {
            background: #f8d7da;
            color: #721c24;
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
                <li><a href="/admin/users.php" class="active">Quản lý nhân viên</a></li>
                <li><a href="/admin/reports.php">Báo cáo</a></li>
                <li><a href="/admin/logout.php">Đăng xuất</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1>Quản lý nhân viên</h1>
                <div>Xin chào, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></div>
            </div>

            <div class="card">
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="showUserModal()">+ Thêm nhân viên mới</button>
                </div>

                <!-- Users Table -->
                <div id="users-container">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Đang tải...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- User Modal -->
    <div id="user-modal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="modal-title">Thêm nhân viên mới</h3>
                <button class="modal-close" onclick="closeUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="user-form">
                    <input type="hidden" id="user-id" name="id">

                    <!-- Thông tin cơ bản -->
                    <h4 style="margin-bottom: 1rem; color: var(--primary);">Thông tin cơ bản</h4>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label form-label-required">Họ và tên</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label form-label-required">Giới tính</label>
                                <select name="gender" class="form-control" required>
                                    <option value="">-- Chọn --</option>
                                    <option value="Nam">Nam</option>
                                    <option value="Nữ">Nữ</option>
                                    <option value="Khác">Khác</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label form-label-required">Số điện thoại</label>
                                <input type="tel" name="contact_info" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Chức danh</label>
                                <input type="text" name="position" class="form-control" placeholder="Nhân viên kinh doanh">
                            </div>
                        </div>
                    </div>

                    <!-- Địa chỉ với cascade dropdown -->
                    <h4 style="margin: 1.5rem 0 1rem; color: var(--primary);">Địa chỉ</h4>

                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label form-label-required">Tỉnh/Thành phố</label>
                                <select name="province_id" id="province-select" class="form-control" required onchange="loadDistricts()">
                                    <option value="">-- Chọn tỉnh --</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label form-label-required">Quận/Huyện</label>
                                <select name="district_id" id="district-select" class="form-control" required onchange="loadWards()">
                                    <option value="">-- Chọn huyện --</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label form-label-required">Phường/Xã</label>
                                <select name="ward_id" id="ward-select" class="form-control" required>
                                    <option value="">-- Chọn xã --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Địa chỉ chi tiết</label>
                        <textarea name="address_detail" class="form-control" rows="2" placeholder="Số nhà, tên đường..."></textarea>
                    </div>

                    <!-- Tài khoản -->
                    <h4 style="margin: 1.5rem 0 1rem; color: var(--primary);">Tài khoản đăng nhập</h4>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label form-label-required">Tên đăng nhập</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" id="password-label">Mật khẩu</label>
                                <input type="password" name="password" class="form-control" id="password-input">
                                <small class="text-muted">Để trống nếu không muốn đổi mật khẩu</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="is_admin" value="1">
                            Quyền quản trị viên
                        </label>
                    </div>

                    <!-- CCCD Upload -->
                    <h4 style="margin: 1.5rem 0 1rem; color: var(--primary);">Căn cước công dân</h4>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Mặt trước CCCD</label>
                                <div class="upload-area" onclick="document.getElementById('id-card-front').click()">
                                    <input type="file" id="id-card-front" name="id_card_front" accept="image/*" onchange="previewIdCard('front')">
                                    <p>Nhấn để chọn ảnh mặt trước</p>
                                    <div id="preview-front"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Mặt sau CCCD</label>
                                <div class="upload-area" onclick="document.getElementById('id-card-back').click()">
                                    <input type="file" id="id-card-back" name="id_card_back" accept="image/*" onchange="previewIdCard('back')">
                                    <p>Nhấn để chọn ảnh mặt sau</p>
                                    <div id="preview-back"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right" style="margin-top: 2rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu thông tin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        let users = [];
        let editingUserId = null;

        // Load users
        async function loadUsers() {
            const container = document.getElementById('users-container');

            try {
                const response = await App.get('users.php');

                if (response.success) {
                    users = response.data.users;
                    displayUsers(users);
                }
            } catch (error) {
                container.innerHTML = '<p class="text-center text-danger p-4">Không thể tải dữ liệu</p>';
            }
        }

        // Display users
        function displayUsers(users) {
            const container = document.getElementById('users-container');

            if (users.length === 0) {
                container.innerHTML = '<p class="text-center text-muted p-4">Chưa có nhân viên nào</p>';
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Nhân viên</th>
                                <th>Giới tính</th>
                                <th>Liên hệ</th>
                                <th>Chức danh</th>
                                <th>Địa chỉ</th>
                                <th>Lần online cuối</th>
                                <th>Tỷ lệ từ chối</th>
                                <th>Tổng đơn</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            users.forEach((user, index) => {
                const avatar = user.full_name.charAt(0).toUpperCase();
                const lastLogin = user.last_login ? App.formatDate(user.last_login) : 'Chưa đăng nhập';
                const rejectionRate = parseFloat(user.rejection_rate || 0);
                const totalOrders = parseInt(user.total_orders || 0);

                let rejectionBadge = '';
                if (rejectionRate === 0) {
                    rejectionBadge = `<span class="stats-badge good">${rejectionRate.toFixed(1)}%</span>`;
                } else if (rejectionRate < 20) {
                    rejectionBadge = `<span class="stats-badge warning">${rejectionRate.toFixed(1)}%</span>`;
                } else {
                    rejectionBadge = `<span class="stats-badge danger">${rejectionRate.toFixed(1)}%</span>`;
                }

                const address = [
                    user.address_detail,
                    user.ward_name,
                    user.district_name,
                    user.province_name
                ].filter(Boolean).join(', ') || '-';

                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div class="user-avatar">${avatar}</div>
                                <div>
                                    <strong>${user.full_name}</strong>
                                    ${user.is_admin ? '<span class="badge badge-primary" style="margin-left: 0.5rem;">Admin</span>' : ''}
                                    <br>
                                    <small class="text-muted">${user.username}</small>
                                </div>
                            </div>
                        </td>
                        <td>${user.gender || '-'}</td>
                        <td>${user.contact_info || '-'}</td>
                        <td>${user.position || '-'}</td>
                        <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${address}">
                            ${address}
                        </td>
                        <td>${lastLogin}</td>
                        <td>${rejectionBadge}</td>
                        <td><strong>${totalOrders}</strong></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">Sửa</button>
                            ${user.id != <?php echo $user['id']; ?> ? `
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Xóa</button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        // Show user modal
        function showUserModal() {
            editingUserId = null;
            document.getElementById('modal-title').textContent = 'Thêm nhân viên mới';
            document.getElementById('user-form').reset();
            document.getElementById('user-id').value = '';
            document.getElementById('password-label').innerHTML = 'Mật khẩu <span class="text-danger">*</span>';
            document.getElementById('password-input').required = true;
            document.getElementById('preview-front').innerHTML = '';
            document.getElementById('preview-back').innerHTML = '';
            document.getElementById('user-modal').style.display = 'flex';
            loadProvinces();
        }

        // Close user modal
        function closeUserModal() {
            document.getElementById('user-modal').style.display = 'none';
        }

        // Edit user
        async function editUser(userId) {
            editingUserId = userId;
            document.getElementById('modal-title').textContent = 'Chỉnh sửa nhân viên';
            document.getElementById('password-label').textContent = 'Mật khẩu mới';
            document.getElementById('password-input').required = false;

            try {
                const response = await App.get(`users.php?id=${userId}`);

                if (response.success) {
                    const user = response.data;

                    document.getElementById('user-id').value = user.id;
                    document.querySelector('[name="full_name"]').value = user.full_name || '';
                    document.querySelector('[name="gender"]').value = user.gender || '';
                    document.querySelector('[name="contact_info"]').value = user.contact_info || '';
                    document.querySelector('[name="position"]').value = user.position || '';
                    document.querySelector('[name="address_detail"]').value = user.address_detail || '';
                    document.querySelector('[name="username"]').value = user.username || '';
                    document.querySelector('[name="is_admin"]').checked = user.is_admin == 1;

                    // Load locations
                    await loadProvinces();
                    if (user.province_id) {
                        document.getElementById('province-select').value = user.province_id;
                        await loadDistricts();
                        if (user.district_id) {
                            document.getElementById('district-select').value = user.district_id;
                            await loadWards();
                            if (user.ward_id) {
                                document.getElementById('ward-select').value = user.ward_id;
                            }
                        }
                    }

                    // Show ID card previews
                    if (user.id_card_front_url) {
                        document.getElementById('preview-front').innerHTML = `<img src="${user.id_card_front_url}" style="max-width: 150px; margin-top: 0.5rem;">`;
                    }
                    if (user.id_card_back_url) {
                        document.getElementById('preview-back').innerHTML = `<img src="${user.id_card_back_url}" style="max-width: 150px; margin-top: 0.5rem;">`;
                    }

                    document.getElementById('user-modal').style.display = 'flex';
                }
            } catch (error) {
                App.showError('Không thể tải thông tin nhân viên');
            }
        }

        // Delete user
        async function deleteUser(userId) {
            if (!App.confirm('Bạn có chắc muốn xóa nhân viên này?')) return;

            try {
                await App.delete(`users.php?id=${userId}`);
                App.showSuccess('Đã xóa nhân viên');
                loadUsers();
            } catch (error) {
                // Error shown by App.delete
            }
        }

        // Load provinces
        async function loadProvinces() {
            try {
                const response = await App.get('locations.php?action=provinces');
                const select = document.getElementById('province-select');

                select.innerHTML = '<option value="">-- Chọn tỉnh --</option>';

                if (response.success) {
                    response.data.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.id;
                        option.textContent = province.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Failed to load provinces:', error);
            }
        }

        // Load districts
        async function loadDistricts() {
            const provinceId = document.getElementById('province-select').value;
            const districtSelect = document.getElementById('district-select');
            const wardSelect = document.getElementById('ward-select');

            districtSelect.innerHTML = '<option value="">-- Chọn huyện --</option>';
            wardSelect.innerHTML = '<option value="">-- Chọn xã --</option>';

            if (!provinceId) return;

            try {
                const response = await App.get(`locations.php?action=districts&province_id=${provinceId}`);

                if (response.success) {
                    response.data.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.id;
                        option.textContent = district.name;
                        districtSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Failed to load districts:', error);
            }
        }

        // Load wards
        async function loadWards() {
            const districtId = document.getElementById('district-select').value;
            const wardSelect = document.getElementById('ward-select');

            wardSelect.innerHTML = '<option value="">-- Chọn xã --</option>';

            if (!districtId) return;

            try {
                const response = await App.get(`locations.php?action=wards&district_id=${districtId}`);

                if (response.success) {
                    response.data.forEach(ward => {
                        const option = document.createElement('option');
                        option.value = ward.id;
                        option.textContent = ward.name;
                        wardSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Failed to load wards:', error);
            }
        }

        // Preview ID card
        function previewIdCard(side) {
            const input = document.getElementById(`id-card-${side}`);
            const preview = document.getElementById(`preview-${side}`);

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" style="max-width: 150px; margin-top: 0.5rem;">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Handle form submission
        document.getElementById('user-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!App.validateForm(e.target)) return;

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');

            App.showLoading(submitBtn);

            try {
                const userId = formData.get('id');
                let response;

                if (userId) {
                    // Update existing user
                    response = await App.putFormData('users.php', formData);
                } else {
                    // Create new user
                    response = await App.postFormData('users.php', formData);
                }

                if (response.success) {
                    App.showSuccess(userId ? 'Đã cập nhật thông tin' : 'Đã thêm nhân viên mới');
                    closeUserModal();
                    loadUsers();
                }
            } catch (error) {
                // Error shown by App methods
            } finally {
                App.hideLoading(submitBtn);
            }
        });

        // Initialize
        loadUsers();

        // Close modal on outside click
        document.getElementById('user-modal').addEventListener('click', (e) => {
            if (e.target.id === 'user-modal') {
                closeUserModal();
            }
        });
    </script>
</body>
</html>
