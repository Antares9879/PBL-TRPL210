/**
 * Session Monitor - Deteksi perubahan session dan auto-reload
 * 
 * Digunakan untuk mendeteksi ketika user login dengan akun berbeda di tab lain,
 * yang menyebabkan session berubah dan CSRF token mismatch.
 * 
 * Praktik industri: Google, Microsoft, Slack, GitHub
 */

class SessionMonitor {
    constructor(options = {}) {
        this.checkInterval = options.checkInterval || 30000; // 30 detik
        this.onSessionExpired = options.onSessionExpired || this.defaultSessionExpiredHandler;
        this.onSessionChanged = options.onSessionChanged || this.defaultSessionChangedHandler;
        this.enabled = options.enabled !== false;
        
        this.currentUserId = null;
        this.currentRole = null;
        this.intervalId = null;
        this.isChecking = false;
        
        // Ambil CSRF token dari meta tag
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Mulai monitoring session
     */
    start() {
        if (!this.enabled) {
            console.log('[SessionMonitor] Monitoring disabled');
            return;
        }

        // Ambil data user saat ini
        this.initializeCurrentUser();

        // Mulai polling
        this.intervalId = setInterval(() => {
            this.checkSession();
        }, this.checkInterval);

        console.log(`[SessionMonitor] Started (interval: ${this.checkInterval}ms)`);
    }

    /**
     * Stop monitoring
     */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
            console.log('[SessionMonitor] Stopped');
        }
    }

    /**
     * Initialize current user data dari DOM atau localStorage
     */
    async initializeCurrentUser() {
        try {
            const response = await fetch('/api/auth/me', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const result = await response.json();
                if (result.status && result.data) {
                    this.currentUserId = result.data.id_pengguna;
                    this.currentRole = result.data.role;
                    console.log('[SessionMonitor] Current user:', {
                        id: this.currentUserId,
                        role: this.currentRole,
                    });
                }
            }
        } catch (error) {
            console.warn('[SessionMonitor] Failed to initialize current user:', error);
        }
    }

    /**
     * Check session validity
     */
    async checkSession() {
        if (this.isChecking) return; // Prevent concurrent checks
        
        this.isChecking = true;

        try {
            const response = await fetch('/api/auth/me', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                credentials: 'same-origin',
            });

            if (response.status === 401) {
                // Session expired atau tidak ada
                console.warn('[SessionMonitor] Session expired (401)');
                this.onSessionExpired();
                return;
            }

            if (response.status === 419) {
                // CSRF token mismatch
                console.warn('[SessionMonitor] CSRF token mismatch (419)');
                this.onSessionChanged('csrf_mismatch');
                return;
            }

            if (response.ok) {
                const result = await response.json();
                
                if (result.status && result.data) {
                    const newUserId = result.data.id_pengguna;
                    const newRole = result.data.role;

                    // Cek apakah user berubah
                    if (this.currentUserId && this.currentUserId !== newUserId) {
                        console.warn('[SessionMonitor] User changed:', {
                            old: { id: this.currentUserId, role: this.currentRole },
                            new: { id: newUserId, role: newRole },
                        });
                        this.onSessionChanged('user_changed', {
                            oldUserId: this.currentUserId,
                            oldRole: this.currentRole,
                            newUserId: newUserId,
                            newRole: newRole,
                        });
                        return;
                    }

                    // Update current user
                    this.currentUserId = newUserId;
                    this.currentRole = newRole;
                }
            }

        } catch (error) {
            console.error('[SessionMonitor] Check failed:', error);
        } finally {
            this.isChecking = false;
        }
    }

    /**
     * Default handler untuk session expired
     */
    defaultSessionExpiredHandler() {
        this.stop();
        this.showNotification(
            'Sesi Anda Telah Berakhir',
            'Halaman akan dimuat ulang untuk login kembali.',
            'warning'
        );
        setTimeout(() => {
            window.location.href = '/login';
        }, 2000);
    }

    /**
     * Default handler untuk session changed
     */
    defaultSessionChangedHandler(reason, data) {
        this.stop();
        
        let message = 'Sesi Anda telah berubah. Halaman akan dimuat ulang.';
        
        if (reason === 'user_changed' && data) {
            const roleMap = {
                'super_admin': 'Super Admin',
                'hr': 'HR',
                'user_departemen': 'User Departemen',
                'admin_outsource': 'Admin Outsource',
                'karyawan': 'Karyawan',
            };
            
            const oldRoleName = roleMap[data.oldRole] || data.oldRole;
            const newRoleName = roleMap[data.newRole] || data.newRole;
            
            message = `Anda telah login sebagai ${newRoleName} di tab lain. Halaman akan dimuat ulang.`;
        }

        this.showNotification(
            'Sesi Berubah',
            message,
            'info'
        );

        setTimeout(() => {
            window.location.reload();
        }, 2500);
    }

    /**
     * Show notification overlay
     */
    showNotification(title, message, type = 'info') {
        // Cek apakah sudah ada notification
        let notification = document.getElementById('session-notification');
        
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'session-notification';
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
            'error': `<svg style="width: 24px; height: 24px; color: #ef4444;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
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

        // Add animation keyframes if not exists
        if (!document.getElementById('session-notification-styles')) {
            const style = document.createElement('style');
            style.id = 'session-notification-styles';
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
}

// Export untuk digunakan di file lain
window.SessionMonitor = SessionMonitor;

// Auto-start jika ada attribute data-session-monitor di body
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    
    if (body.hasAttribute('data-session-monitor')) {
        const interval = parseInt(body.getAttribute('data-session-monitor-interval')) || 30000;
        
        const monitor = new SessionMonitor({
            checkInterval: interval,
            enabled: true,
        });
        
        monitor.start();
        
        // Expose ke window untuk debugging
        window.sessionMonitor = monitor;
    }
});
