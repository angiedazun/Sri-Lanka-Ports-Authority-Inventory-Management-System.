/**
 * Modal Dialog System
 * Beautiful, accessible modal dialogs
 */

class Modal {
    static show(options = {}) {
        const defaults = {
            title: 'Modal',
            content: '',
            type: 'default', // default, confirm, danger
            showClose: true,
            closeOnBackdrop: true,
            buttons: [],
            size: 'medium', // small, medium, large, full
            onClose: null
        };
        
        const config = { ...defaults, ...options };
        
        // Remove existing modals
        this.closeAll();
        
        // Create modal
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.id = 'modal-overlay';
        
        const modalClass = `modal-dialog modal-${config.size} modal-${config.type}`;
        
        modal.innerHTML = `
            <div class="${modalClass}">
                <div class="modal-header">
                    <h3 class="modal-title">
                        ${this.getIcon(config.type)}
                        ${config.title}
                    </h3>
                    ${config.showClose ? '<button class="modal-close" data-action="close"><i class="fas fa-times"></i></button>' : ''}
                </div>
                <div class="modal-body">
                    ${config.content}
                </div>
                ${config.buttons.length > 0 ? this.renderButtons(config.buttons) : ''}
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Event listeners
        if (config.closeOnBackdrop) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.close(config.onClose);
                }
            });
        }
        
        modal.querySelectorAll('[data-action="close"]').forEach(btn => {
            btn.addEventListener('click', () => this.close(config.onClose));
        });
        
        modal.querySelectorAll('[data-callback]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const callback = e.target.closest('button').dataset.callback;
                if (config[callback]) {
                    config[callback]();
                }
            });
        });
        
        // Animate in
        setTimeout(() => modal.classList.add('show'), 10);
        
        return modal;
    }

    static close(callback = null) {
        const modal = document.getElementById('modal-overlay');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.remove();
                document.body.style.overflow = '';
                if (callback) callback();
            }, 300);
        }
    }

    static closeAll() {
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.remove();
        });
        document.body.style.overflow = '';
    }

    static confirm(options = {}) {
        return new Promise((resolve) => {
            this.show({
                title: options.title || 'Confirm Action',
                content: options.message || 'Are you sure?',
                type: options.type || 'confirm',
                buttons: [
                    {
                        text: options.cancelText || 'Cancel',
                        className: 'btn-secondary',
                        callback: 'onCancel'
                    },
                    {
                        text: options.confirmText || 'Confirm',
                        className: options.type === 'danger' ? 'btn-danger' : 'btn-primary',
                        callback: 'onConfirm'
                    }
                ],
                onCancel: () => {
                    this.close();
                    resolve(false);
                },
                onConfirm: () => {
                    this.close();
                    resolve(true);
                }
            });
        });
    }

    static alert(options = {}) {
        return new Promise((resolve) => {
            this.show({
                title: options.title || 'Alert',
                content: options.message || '',
                type: options.type || 'default',
                buttons: [
                    {
                        text: options.buttonText || 'OK',
                        className: 'btn-primary',
                        callback: 'onOk'
                    }
                ],
                onOk: () => {
                    this.close();
                    resolve(true);
                }
            });
        });
    }

    static renderButtons(buttons) {
        const buttonsHtml = buttons.map(btn => `
            <button class="btn ${btn.className}" data-callback="${btn.callback}">
                ${btn.icon ? `<i class="${btn.icon}"></i>` : ''}
                ${btn.text}
            </button>
        `).join('');
        
        return `<div class="modal-footer">${buttonsHtml}</div>`;
    }

    static getIcon(type) {
        const icons = {
            default: '<i class="fas fa-info-circle"></i>',
            confirm: '<i class="fas fa-question-circle"></i>',
            danger: '<i class="fas fa-exclamation-triangle"></i>',
            success: '<i class="fas fa-check-circle"></i>',
            warning: '<i class="fas fa-exclamation-circle"></i>'
        };
        return icons[type] || '';
    }
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Modal;
}
