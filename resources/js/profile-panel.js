/**
 * resources/js/profile-panel.js
 *
 * Panel overlay profil pengguna (pojok kanan atas).
 * Bekerja di semua layout:
 *   - app.blade.php, admin.blade.php  → avatar selector: .topbar-avatar
 *   - karyawan.blade.php              → avatar selector: .k-topbar-avatar
 *
 * Import di resources/js/app.js:
 *   import './profile-panel.js';
 */

(function () {
    'use strict';

    // ── Konstanta ────────────────────────────────────────────────────────────

    const OPEN_CLASS   = 'profile-panel--open';

    // Selector avatar mencakup semua layout
    const AVATAR_SELECTORS = ['.topbar-avatar', '.k-topbar-avatar'];

    // ── Referensi DOM ────────────────────────────────────────────────────────

    const overlay    = document.getElementById('profile-panel-overlay');
    const backdrop   = document.getElementById('profile-panel-backdrop');
    const panel      = document.getElementById('profile-panel');
    const btnLogout  = document.getElementById('btn-profile-logout');
    const logoutForm = document.getElementById('profile-logout-form');

    // Guard: panel tidak ada di halaman ini (mis. guest layout)
    if (!overlay || !panel) return;

    // ── Helpers ──────────────────────────────────────────────────────────────

    function openPanel() {
        overlay.classList.add(OPEN_CLASS);
        overlay.setAttribute('aria-hidden', 'false');
        panel.setAttribute('aria-modal', 'true');

        // Fokus ke panel untuk aksesibilitas
        panel.setAttribute('tabindex', '-1');
        panel.focus({ preventScroll: true });
    }

    function closePanel() {
        overlay.classList.remove(OPEN_CLASS);
        overlay.setAttribute('aria-hidden', 'true');
        panel.setAttribute('aria-modal', 'false');
    }

    function togglePanel() {
        if (overlay.classList.contains(OPEN_CLASS)) {
            closePanel();
        } else {
            openPanel();
        }
    }

    // ── Event: klik avatar (semua layout) ───────────────────────────────────

    AVATAR_SELECTORS.forEach(function (sel) {
        const avatarEl = document.querySelector(sel);
        if (!avatarEl) return;

        avatarEl.addEventListener('click', function (e) {
            e.stopPropagation();
            togglePanel();
        });

        // Keyboard support: Enter / Space
        avatarEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePanel();
            }
        });
    });

    // ── Event: klik backdrop → tutup ────────────────────────────────────────

    backdrop.addEventListener('click', closePanel);

    // ── Event: klik di luar panel → tutup ───────────────────────────────────

    document.addEventListener('click', function (e) {
        if (!overlay.classList.contains(OPEN_CLASS)) return;
        if (panel.contains(e.target)) return;

        // Pastikan klik bukan dari avatar (sudah ditangani sendiri)
        const clickedAvatar = AVATAR_SELECTORS.some(function (sel) {
            const el = document.querySelector(sel);
            return el && el.contains(e.target);
        });
        if (clickedAvatar) return;

        closePanel();
    });

    // ── Event: Escape → tutup ────────────────────────────────────────────────

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains(OPEN_CLASS)) {
            closePanel();
        }
    });

    // ── Event: tombol Logout ─────────────────────────────────────────────────

    if (btnLogout && logoutForm) {
        btnLogout.addEventListener('click', function () {
            logoutForm.submit();
        });
    }

})();