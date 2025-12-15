/**
 * Loading Overlay System
 * Professional loading indicators
 */

class Loading {
    static show(message = 'Loading...') {
        // Remove existing overlay
        this.hide();
        
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.className = 'loading-overlay';
        
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
                <div class="loading-text">${message}</div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Animate in
        setTimeout(() => overlay.classList.add('show'), 10);
    }

    static hide() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => overlay.remove(), 300);
        }
    }

    static update(message) {
        const textElement = document.querySelector('.loading-text');
        if (textElement) {
            textElement.textContent = message;
        }
    }
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Loading;
}
