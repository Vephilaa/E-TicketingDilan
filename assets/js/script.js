// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.length <= 1) {
                return;
            }
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            
            // Animate hamburger menu
            const spans = navToggle.querySelectorAll('span');
            spans.forEach((span, index) => {
                if (navMenu.classList.contains('active')) {
                    if (index === 0) span.style.transform = 'rotate(45deg) translate(5px, 5px)';
                    if (index === 1) span.style.opacity = '0';
                    if (index === 2) span.style.transform = 'rotate(-45deg) translate(7px, -6px)';
                } else {
                    span.style.transform = 'none';
                    span.style.opacity = '1';
                }
            });
        });
    }

    autoHideAlerts();
    enhanceDatePickers();
});

// Form Validation
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            showError(input, 'Field ini harus diisi');
            isValid = false;
        } else {
            clearError(input);
        }
        
        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                showError(input, 'Format email tidak valid');
                isValid = false;
            }
        }
        
        // Phone validation
        if (input.type === 'tel' && input.value) {
            const phoneRegex = /^[+]?[0-9\s\-\(\)]+$/;
            if (!phoneRegex.test(input.value)) {
                showError(input, 'Format nomor telepon tidak valid');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

function showError(input, message) {
    clearError(input);
    
    const errorElement = document.createElement('div');
    errorElement.className = 'error-message';
    errorElement.textContent = message;
    errorElement.style.color = '#ef4444';
    errorElement.style.fontSize = '0.875rem';
    errorElement.style.marginTop = '0.25rem';
    
    input.style.borderColor = '#ef4444';
    input.parentNode.appendChild(errorElement);
}

function clearError(input) {
    input.style.borderColor = '';
    const errorElement = input.parentNode.querySelector('.error-message');
    if (errorElement) {
        errorElement.remove();
    }
}

// Auto-hide alerts
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Search form validation
function validateSearchForm(form) {
    const departureAirport = form.querySelector('#departure_airport');
    const arrivalAirport = form.querySelector('#arrival_airport');
    const departureDate = form.querySelector('#departure_date');
    
    let isValid = true;
    
    if (departureAirport.value === arrivalAirport.value) {
        showError(arrivalAirport, 'Bandara tujuan harus berbeda dengan bandara asal');
        isValid = false;
    }
    
    const selectedDate = new Date(departureDate.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        showError(departureDate, 'Tanggal keberangkatan tidak boleh kurang dari hari ini');
        isValid = false;
    }
    
    return isValid;
}

// Booking form validation
function validateBookingForm(form) {
    const passengers = [];
    const passengerCountInput = form.querySelector('input[name="passengers_count"]');
    const passengerCount = passengerCountInput ? parseInt(passengerCountInput.value) : 1;
    
    for (let i = 1; i <= passengerCount; i++) {
        const name = form.querySelector(`#passenger_name_${i}`);
        const idNumber = form.querySelector(`#passenger_id_${i}`);
        const birthDate = form.querySelector(`#passenger_birth_${i}`);
        const gender = form.querySelector(`input[name="passenger_gender_${i}"]:checked`);
        
        if (!name || !name.value.trim()) {
            showError(name, 'Nama penumpang harus diisi');
            return false;
        }
        
        if (!idNumber || !idNumber.value.trim()) {
            showError(idNumber, 'Nomor identitas harus diisi');
            return false;
        }
        
        if (!birthDate || !birthDate.value) {
            showError(birthDate, 'Tanggal lahir harus diisi');
            return false;
        }
        
        if (!gender) {
            const genderGroup = form.querySelector(`input[name="passenger_gender_${i}"]`);
            if (genderGroup) {
                showError(genderGroup.parentNode, 'Jenis kelamin harus dipilih');
            }
            return false;
        }
        
        // Validate birth date (not future date)
        const birth = new Date(birthDate.value);
        const today = new Date();
        if (birth > today) {
            showError(birthDate, 'Tanggal lahir tidak boleh di masa depan');
            return false;
        }
        
        passengers.push({
            name: name.value.trim(),
            idNumber: idNumber.value.trim(),
            birthDate: birthDate.value,
            gender: gender.value
        });
    }
    
    return true;
}

// File upload validation
function validateFileUpload(input, maxSize = 5242880, allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']) {
    const file = input.files[0];
    
    if (!file) {
        showError(input, 'Silakan pilih file');
        return false;
    }
    
    if (!allowedTypes.includes(file.type)) {
        showError(input, 'Format file tidak diizinkan. Hanya JPG, JPEG, dan PNG yang diperbolehkan.');
        return false;
    }
    
    if (file.size > maxSize) {
        showError(input, 'Ukuran file terlalu besar. Maksimal 5MB.');
        return false;
    }
    
    clearError(input);
    return true;
}

// Format currency input
function formatCurrencyInput(input) {
    let value = input.value.replace(/[^\d]/g, '');
    if (value) {
        value = parseInt(value);
        input.value = value.toLocaleString('id-ID');
    }
}

// Date picker enhancements
function enhanceDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => {
        const id = (input.id || '').toLowerCase();
        const name = (input.name || '').toLowerCase();

        if (id.includes('departure') || name.includes('departure')) {
            input.min = today;
        }

        if (id.includes('birth') || name.includes('birth')) {
            input.max = today;
            input.removeAttribute('min');
        }
        
        // Add date formatting on change
        input.addEventListener('change', function() {
            if (this.value) {
                const date = new Date(this.value);
                const formatted = date.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                this.title = formatted;
            }
        });
    });
}

// Loading states for buttons
function setButtonLoading(button, loading = true) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.textContent;
        button.innerHTML = '<span class="spinner"></span> Memproses...';
        button.style.cursor = 'not-allowed';
    } else {
        button.disabled = false;
        button.textContent = button.dataset.originalText;
        button.style.cursor = 'pointer';
    }
}

// Add spinner styles
const spinnerStyles = `
    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = spinnerStyles;
document.head.appendChild(styleSheet);

// Form submission handlers
document.addEventListener('submit', function(e) {
    const form = e.target;
    
    // Add loading state to submit button
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
        setButtonLoading(submitButton, true);
    }
    
    // Validate form based on its type
    let isValid = true;
    
    if (form.classList.contains('auth-form') || form.classList.contains('form')) {
        isValid = validateForm(form);
    } else if (form.classList.contains('search-form')) {
        isValid = validateSearchForm(form);
    } else if (form.classList.contains('booking-form')) {
        isValid = validateBookingForm(form);
    }
    
    if (!isValid) {
        e.preventDefault();
        if (submitButton) {
            setButtonLoading(submitButton, false);
        }
    }
});

// File upload handlers
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        if (this.files[0]) {
            const file = this.files[0];
            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            
            // Show file info
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-info';
            fileInfo.style.marginTop = '0.5rem';
            fileInfo.style.fontSize = '0.875rem';
            fileInfo.style.color = '#6b7280';
            fileInfo.textContent = `File: ${fileName} (${fileSize} MB)`;
            
            // Remove existing file info
            const existingInfo = this.parentNode.querySelector('.file-info');
            if (existingInfo) {
                existingInfo.remove();
            }
            
            this.parentNode.appendChild(fileInfo);
        }
    });
});

// Print ticket functionality
function printTicket() {
    window.print();
}

// Copy to clipboard functionality
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = button.textContent;
        button.textContent = 'Tersalin!';
        button.style.backgroundColor = '#10b981';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
        }, 2000);
    }).catch(err => {
        console.error('Gagal menyalin teks:', err);
    });
}

// Initialize tooltips
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: #1f2937;
                color: white;
                padding: 0.5rem;
                border-radius: 0.25rem;
                font-size: 0.875rem;
                z-index: 1000;
                white-space: nowrap;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });
}

// Initialize tooltips
initTooltips();
