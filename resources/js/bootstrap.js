import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Axios Interceptor - Handle 401 dan 419 errors
 * 
 * 401 Unauthorized: Session expired
 * 419 CSRF Token Mismatch: Session changed (user login di tab lain)
 */
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response) {
            const status = error.response.status;
            
            // 401 Unauthorized - Session expired
            if (status === 401) {
                console.warn('[Axios] 401 Unauthorized - Session expired');
                
                // Jangan redirect jika sudah di halaman login
                if (!window.location.pathname.includes('/login')) {
                    showSessionExpiredNotification();
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                }
            }
            
            // 419 CSRF Token Mismatch - Session changed
            if (status === 419) {
                console.warn('[Axios] 419 CSRF Token Mismatch - Session changed');
                
                showSessionChangedNotification();
                setTimeout(() => {
                    window.location.reload();
                }, 2500);
            }
        }
        
        return Promise.reject(error);
    }
);

/**
 * Show session expired notification
 */
function showSessionExpiredNotification() {
    showNotification(
        'Sesi Anda Telah Berakhir',
        'Halaman akan dimuat ulang untuk login kembali.',
        'warning'
    );
}

/**
 * Show session changed notification
 */
function showSessionChangedNotification() {
    showNotification(
        'Sesi Berubah',
        'Anda telah login dengan akun lain di tab berbeda. Halaman akan dimuat ulang.',
        'info'
    );
}

/**
 * Show notification overlay
 */
function showNotification(title, message, type = 'info') {
    let notification = document.getElementById('axios-notification');
    
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'axios-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 20px;
            z-index: 99999;
            animation: slideInRight 0.3s ease-out;
            font-family: 'DM Sans', system-ui, sans-serif;
        `;
        document.body.appendChild(notification);
    }

    const iconMap = {
        'info': `<svg style="width: 24px; height: 24px; color: #3b82f6;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
        </svg>`,
        'warning': `<svg style="width: 24px; height: 24px; color: #f59e0b;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>`,
    };

    notification.innerHTML = `
        <div style="display: flex; gap: 12px; align-items: start;">
            <div style="flex-shrink: 0;">
                ${iconMap[type] || iconMap['info']}
            </div>
            <div style="flex: 1;">
                <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #1f2937;">
                    ${title}
                </h3>
                <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.5;">
                    ${message}
                </p>
            </div>
        </div>
    `;

    if (!document.getElementById('axios-notification-styles')) {
        const style = document.createElement('style');
        style.id = 'axios-notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }
}
