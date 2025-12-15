/**
 * Main JavaScript - Core Utilities
 */

const App = {
    // Configuration
    config: {
        apiBaseUrl: '/api',
        csrfToken: ''
    },

    // Initialize
    init() {
        this.setupCSRF();
        this.setupAjaxDefaults();
    },

    // Setup CSRF token
    setupCSRF() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            this.config.csrfToken = token.getAttribute('content');
        }
    },

    // Setup AJAX defaults
    setupAjaxDefaults() {
        // Add CSRF token to all POST requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            if (args[1] && args[1].method === 'POST') {
                args[1].headers = args[1].headers || {};
                args[1].headers['X-CSRF-Token'] = App.config.csrfToken;
            }
            return originalFetch.apply(this, args);
        };
    },

    // API request helper
    async request(endpoint, options = {}) {
        const url = `${this.config.apiBaseUrl}/${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Request failed');
            }

            return data;
        } catch (error) {
            this.showError(error.message);
            throw error;
        }
    },

    // GET request
    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    // POST request
    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    // PUT request
    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    // DELETE request
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    },

    // Show success message
    showSuccess(message) {
        this.showToast(message, 'success');
    },

    // Show error message
    showError(message) {
        this.showToast(message, 'danger');
    },

    // Show toast notification
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} toast-notification`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 250px;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    // Form validation
    validateForm(formElement) {
        const inputs = formElement.querySelectorAll('[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                this.showFieldError(input, 'Trường này là bắt buộc');
                isValid = false;
            } else {
                this.clearFieldError(input);
            }

            // Validate phone
            if (input.type === 'tel' && input.value) {
                if (!this.validatePhone(input.value)) {
                    this.showFieldError(input, 'Số điện thoại không hợp lệ');
                    isValid = false;
                }
            }

            // Validate email
            if (input.type === 'email' && input.value) {
                if (!this.validateEmail(input.value)) {
                    this.showFieldError(input, 'Email không hợp lệ');
                    isValid = false;
                }
            }
        });

        return isValid;
    },

    // Show field error
    showFieldError(input, message) {
        input.classList.add('is-invalid');
        let errorDiv = input.parentElement.querySelector('.form-error');

        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'form-error';
            input.parentElement.appendChild(errorDiv);
        }

        errorDiv.textContent = message;
    },

    // Clear field error
    clearFieldError(input) {
        input.classList.remove('is-invalid');
        const errorDiv = input.parentElement.querySelector('.form-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    },

    // Validate Vietnamese phone number
    validatePhone(phone) {
        return /^(0[3|5|7|8|9])+([0-9]{8})$/.test(phone.replace(/[^0-9]/g, ''));
    },

    // Validate email
    validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    // Get geolocation
    async getGeolocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                position => resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                }),
                error => reject(error)
            );
        });
    },

    // Format currency (VND)
    formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    },

    // Format date
    formatDate(date) {
        return new Date(date).toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    // Show loading
    showLoading(element) {
        element.disabled = true;
        element.dataset.originalText = element.innerHTML;
        element.innerHTML = '<span class="spinner"></span> Đang xử lý...';
    },

    // Hide loading
    hideLoading(element) {
        element.disabled = false;
        element.innerHTML = element.dataset.originalText;
    },

    // Confirm dialog
    confirm(message) {
        return window.confirm(message);
    },

    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .is-invalid {
        border-color: var(--danger) !important;
    }
`;
document.head.appendChild(style);

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
