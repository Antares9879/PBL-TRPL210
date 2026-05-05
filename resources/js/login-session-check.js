/**
 * Login Session Check - Warning jika sudah ada session aktif
 * 
 * Menampilkan warning di halaman login jika user sudah login di tab lain.
 * Memberikan informasi bahwa login dengan akun berbeda akan logout session sebelumnya.
 */

document.addEventListener('DOMContentLoaded', async () => {
    
    const alertContainer = document.getElementById('alert-container');
    const alertIcon = document.getElementById('alert-icon');
    const alertMessage = document.getElementById('alert-message');
    
    // Cek apakah sudah ada session aktif
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        const response = await fetch('/api/auth/me', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const result = await response.json();
            
            if (result.status && result.data) {
                // Ada session aktif
                const roleMap = {
                    'super_admin': 'Super Admin',
                    'hr': 'HR',
                    'user_departemen': 'User Departemen',
                    'admin_outsource': 'Admin Outsource',
                    'karyawan': 'Karyawan',
                };
                
                const roleName = roleMap[result.data.role] || result.data.role;
                const userName = result.data.nama_lengkap || 'User';
                
                // Tampilkan warning
                showWarning(
                    `Anda sudah login sebagai <strong>${userName}</strong> (${roleName}). ` +
                    `Login dengan akun lain akan mengakhiri sesi sebelumnya di tab lain.`
                );
            }
        }
        
    } catch (error) {
        // Ignore error - tidak perlu warning jika gagal cek session
        console.log('[LoginSessionCheck] Failed to check session:', error);
    }

    /**
     * Show warning alert
     */
    function showWarning(message) {
        if (!alertContainer || !alertIcon || !alertMessage) return;
        
        alertMessage.innerHTML = message;
        alertContainer.classList.remove('login-alert--hidden', 'login-alert--error', 'login-alert--success');
        alertContainer.classList.add('login-alert--warning');
        
        // Icon untuk warning
        alertIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        `;
        
        // Scroll ke alert
        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
});
