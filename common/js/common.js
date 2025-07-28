/**
 * Common JavaScript Functions for TTT ZOOM System
 * Version: 2.0.0
 */

// Global configuration
const TTT_CONFIG = {
    baseUrl: window.location.origin + '/TTT_NOMS_ZOOM',
    apiTimeout: 30000,
    maxFileSize: 5 * 1024 * 1024 // 5MB
};

// Utility Functions
const Utils = {
    /**
     * Show loading spinner
     */
    showLoading: function(element) {
        if (element) {
            element.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';
        }
    },

    /**
     * Hide loading spinner
     */
    hideLoading: function(element, originalText = '') {
        if (element) {
            element.innerHTML = originalText;
        }
    },

    /**
     * Show alert message
     */
    showAlert: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    },

    /**
     * Format file size
     */
    formatFileSize: function(bytes) {
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 Byte';
        const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    },

    /**
     * Validate email format
     */
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    /**
     * Validate file upload
     */
    validateFile: function(file) {
        const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
                             'application/vnd.ms-excel', 
                             'text/csv'];
        
        if (!allowedTypes.includes(file.type)) {
            return {valid: false, message: 'Invalid file type. Please upload Excel or CSV files only.'};
        }
        
        if (file.size > TTT_CONFIG.maxFileSize) {
            return {valid: false, message: `File size exceeds ${this.formatFileSize(TTT_CONFIG.maxFileSize)} limit.`};
        }
        
        return {valid: true, message: 'File is valid.'};
    }
};

// AJAX Helper Functions
const Ajax = {
    /**
     * Make GET request
     */
    get: function(url, callback, errorCallback = null) {
        fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => {
            console.error('Ajax GET Error:', error);
            if (errorCallback) errorCallback(error);
        });
    },

    /**
     * Make POST request
     */
    post: function(url, data, callback, errorCallback = null) {
        const formData = new FormData();
        for (const key in data) {
            formData.append(key, data[key]);
        }

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => {
            console.error('Ajax POST Error:', error);
            if (errorCallback) errorCallback(error);
        });
    }
};

// Form Validation
const Validation = {
    /**
     * Validate registration form
     */
    validateRegistrationForm: function(form) {
        const errors = [];
        
        const email = form.querySelector('input[name="email"]');
        if (email && email.value && !Utils.isValidEmail(email.value)) {
            errors.push('Please enter a valid email address.');
        }
        
        const required = form.querySelectorAll('[required]');
        required.forEach(field => {
            if (!field.value.trim()) {
                errors.push(`${field.getAttribute('data-label') || field.name} is required.`);
            }
        });
        
        return errors;
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize any data tables if they exist
    if (typeof DataTable !== 'undefined') {
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(table => {
            new DataTable(table, {
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']]
            });
        });
    }
});
