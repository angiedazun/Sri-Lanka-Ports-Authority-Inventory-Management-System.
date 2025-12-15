/**
 * Smooth Scroll & Page Transitions
 */

class PageTransition {
    static init() {
        // Add smooth page transitions
        this.addPageTransition();
        
        // Add smooth scroll behavior
        this.addSmoothScroll();
        
        // Add entrance animations
        this.addEntranceAnimations();
    }

    static addPageTransition() {
        // Fade out on page unload
        window.addEventListener('beforeunload', () => {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.3s ease';
        });
        
        // Fade in on page load
        window.addEventListener('load', () => {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
            }, 10);
        });
    }

    static addSmoothScroll() {
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                e.preventDefault();
                const target = document.querySelector(href);
                
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    static addEntranceAnimations() {
        // Observe elements for entrance animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Add animation class to elements
        document.querySelectorAll('.fade-in-up, .fade-in, .slide-in-left, .slide-in-right').forEach(el => {
            observer.observe(el);
        });
    }
}

/**
 * Form Enhancements
 */

class FormEnhancer {
    static init() {
        this.addFloatingLabels();
        this.addPasswordToggle();
        this.addFormValidation();
        this.addFileUploadPreview();
    }

    static addFloatingLabels() {
        document.querySelectorAll('.form-floating input, .form-floating textarea').forEach(input => {
            // Add focus class on focus
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });
            
            // Remove focus class on blur if empty
            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.parentElement.classList.remove('focused');
                }
            });
            
            // Check initial value
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        });
    }

    static addPasswordToggle() {
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }

    static addFormValidation() {
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate required fields
                form.querySelectorAll('[required]').forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                        FormEnhancer.showFieldError(field, 'This field is required');
                    } else {
                        field.classList.remove('is-invalid');
                        FormEnhancer.hideFieldError(field);
                    }
                });
                
                // Validate email fields
                form.querySelectorAll('[type="email"]').forEach(field => {
                    if (field.value && !FormEnhancer.isValidEmail(field.value)) {
                        isValid = false;
                        field.classList.add('is-invalid');
                        FormEnhancer.showFieldError(field, 'Please enter a valid email');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    Toast.error('Please fix the errors in the form');
                }
            });
        });
    }

    static addFileUploadPreview() {
        document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
            input.addEventListener('change', function() {
                const file = this.files[0];
                const previewId = this.dataset.preview;
                const preview = document.getElementById(previewId);
                
                if (file && preview) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        if (file.type.startsWith('image/')) {
                            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; border-radius: 8px;">`;
                        } else {
                            preview.innerHTML = `<div class="badge badge-info"><i class="fas fa-file"></i> ${file.name}</div>`;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    }

    static showFieldError(field, message) {
        let error = field.nextElementSibling;
        if (!error || !error.classList.contains('field-error')) {
            error = document.createElement('div');
            error.className = 'field-error';
            field.parentNode.insertBefore(error, field.nextSibling);
        }
        error.textContent = message;
    }

    static hideFieldError(field) {
        const error = field.nextElementSibling;
        if (error && error.classList.contains('field-error')) {
            error.remove();
        }
    }

    static isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
}

/**
 * Table Enhancements
 */

class TableEnhancer {
    static init() {
        this.addSortable();
        this.addSearchFilter();
        this.addRowActions();
    }

    static addSortable() {
        document.querySelectorAll('table.sortable thead th[data-sort]').forEach(th => {
            th.style.cursor = 'pointer';
            th.innerHTML += ' <i class="fas fa-sort sort-icon"></i>';
            
            th.addEventListener('click', function() {
                const table = this.closest('table');
                const column = this.dataset.sort;
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = this.classList.contains('sort-asc');
                
                // Remove sort classes from all headers
                table.querySelectorAll('th').forEach(header => {
                    header.classList.remove('sort-asc', 'sort-desc');
                });
                
                // Sort rows
                rows.sort((a, b) => {
                    const aVal = a.children[column].textContent.trim();
                    const bVal = b.children[column].textContent.trim();
                    
                    if (isAsc) {
                        return bVal.localeCompare(aVal, undefined, {numeric: true});
                    } else {
                        return aVal.localeCompare(bVal, undefined, {numeric: true});
                    }
                });
                
                // Update table
                rows.forEach(row => tbody.appendChild(row));
                
                // Update sort class
                this.classList.add(isAsc ? 'sort-desc' : 'sort-asc');
            });
        });
    }

    static addSearchFilter() {
        document.querySelectorAll('.table-search').forEach(search => {
            const tableId = search.dataset.table;
            const table = document.getElementById(tableId);
            
            if (!table) return;
            
            search.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        });
    }

    static addRowActions() {
        document.querySelectorAll('.table-row-action').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const row = this.closest('tr');
                row.classList.add('highlight');
                setTimeout(() => row.classList.remove('highlight'), 1000);
            });
        });
    }
}

// Initialize all enhancements on page load
document.addEventListener('DOMContentLoaded', () => {
    PageTransition.init();
    FormEnhancer.init();
    TableEnhancer.init();
});
