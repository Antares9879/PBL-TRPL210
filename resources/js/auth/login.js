/**
 * resources/js/auth/login.js
 * Handle form login via AJAX — E-Outsourcing PBL-TRPL210
 *
 * Response format yang diharapkan dari backend:
 *   { "status": true,  "message": "...", "data": { "redirect": "/karyawan/dashboard" } }
 *   { "status": false, "message": "...", "data": null }
 */

document.addEventListener('DOMContentLoaded', () => {

    // ─── Elemen DOM ───────────────────────────────────────────────────────────
    const emailInput        = document.getElementById('login-email');
    const passwordInput     = document.getElementById('login-password');
    const rememberCheckbox  = document.getElementById('remember-me');
    const btnLogin          = document.getElementById('btn-login');
    const btnLoginText      = document.getElementById('btn-login-text');
    const btnLoginSpinner   = document.getElementById('btn-login-spinner');

    const alertContainer    = document.getElementById('alert-container');
    const alertIcon         = document.getElementById('alert-icon');
    const alertMessage      = document.getElementById('alert-message');

    const emailError        = document.getElementById('email-error');
    const emailErrorText    = document.getElementById('email-error-text');
    const passwordError     = document.getElementById('password-error');
    const passwordErrorText = document.getElementById('password-error-text');

    // Toggle password — satu tombol, satu SVG dengan 3 path
    const togglePasswordBtn = document.getElementById('toggle-password');
    const eyePathMain       = document.getElementById('eye-path-main');   // lingkaran pupil
    const eyePathOuter      = document.getElementById('eye-path-outer');  // outline mata
    const eyePathSlash      = document.getElementById('eye-path-slash');  // garis coret (eye-off)

    // Path SVG untuk state "eye" (password tersembunyi → tampilkan ikon mata terbuka)
    const PATH_EYE_MAIN  = 'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z';
    const PATH_EYE_OUTER = 'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z';

    // Path SVG untuk state "eye-off" (password terlihat → tampilkan ikon mata dicoret)
    const PATH_EYEOFF_MAIN  = 'M13.875 18.825A10.05 10.05 0 0 1 12 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 0 1 4.34-5.592m2.978-.87A9.956 9.956 0 0 1 12 5c4.478 0 8.268 2.943 9.543 7a9.96 9.96 0 0 1-1.805 3.244';
    const PATH_EYEOFF_OUTER = 'M15 12a3 3 0 0 0-3-3m0 0a3 3 0 0 0-2.12.88';

    // CSRF token dari meta tag (wajib dikirim di setiap request POST/PUT/DELETE)
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ─── Toggle visibilitas password ──────────────────────────────────────────
    togglePasswordBtn.addEventListener('click', () => {
        const isHidden = togglePasswordBtn.dataset.state === 'hidden';

        if (isHidden) {
            // Tampilkan teks password → ganti ke ikon eye-off
            passwordInput.type                  = 'text';
            togglePasswordBtn.dataset.state     = 'visible';
            togglePasswordBtn.setAttribute('aria-label', 'Sembunyikan password');

            eyePathMain.setAttribute('d', PATH_EYEOFF_MAIN);
            eyePathOuter.setAttribute('d', PATH_EYEOFF_OUTER);
            eyePathSlash.classList.remove('eye-hidden');   // tampilkan garis coret

        } else {
            // Sembunyikan teks password → kembali ke ikon eye
            passwordInput.type                  = 'password';
            togglePasswordBtn.dataset.state     = 'hidden';
            togglePasswordBtn.setAttribute('aria-label', 'Tampilkan password');

            eyePathMain.setAttribute('d', PATH_EYE_MAIN);
            eyePathOuter.setAttribute('d', PATH_EYE_OUTER);
            eyePathSlash.classList.add('eye-hidden');      // sembunyikan garis coret
        }

        // Kembalikan fokus ke input password setelah toggle
        passwordInput.focus();
    });

    // ─── Real-time validation (bersihkan error saat user mengetik) ───────────
    emailInput.addEventListener('input',    () => clearFieldError('email'));
    passwordInput.addEventListener('input', () => clearFieldError('password'));

    // ─── Submit via Enter key ─────────────────────────────────────────────────
    [emailInput, passwordInput].forEach(el => {
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') handleLogin();
        });
    });

    // ─── Click handler tombol submit ─────────────────────────────────────────
    btnLogin.addEventListener('click', handleLogin);

    // ─── Fungsi utama login ───────────────────────────────────────────────────
    async function handleLogin() {
        clearAllErrors();
        hideAlert();

        const email    = emailInput.value.trim();
        const password = passwordInput.value;
        const remember = rememberCheckbox.checked;

        // Validasi sisi klien
        let hasError = false;

        if (!email) {
            showFieldError('email', 'Email tidak boleh kosong.');
            hasError = true;
        } else if (!isValidEmail(email)) {
            showFieldError('email', 'Format email tidak valid.');
            hasError = true;
        }

        if (!password) {
            showFieldError('password', 'Password tidak boleh kosong.');
            hasError = true;
        } else if (password.length < 6) {
            showFieldError('password', 'Password minimal 6 karakter.');
            hasError = true;
        }

        if (hasError) return;

        setLoading(true);

        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-CSRF-TOKEN':     csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ email, password, remember }),
            });

            const result = await response.json();

            if (result.status === true) {
                showAlert('success', result.message ?? 'Login berhasil. Mengalihkan...');
                const redirectUrl = result.data?.redirect ?? '/dashboard';
                setTimeout(() => { window.location.href = redirectUrl; }, 800);

            } else {
                showAlert('error', result.message ?? 'Email atau password tidak valid.');
                setLoading(false);

                // Visual shake pada input
                emailInput.classList.add('login-input--error');
                passwordInput.classList.add('login-input--error');
            }

        } catch (err) {
            console.error('[Login] Request gagal:', err);
            showAlert('error', 'Gagal terhubung ke server. Periksa koneksi Anda dan coba lagi.');
            setLoading(false);
        }
    }

    // ─── Helper: loading state ────────────────────────────────────────────────
    function setLoading(isLoading) {
        btnLogin.disabled          = isLoading;
        btnLoginText.textContent   = isLoading ? 'Memproses...' : 'Masuk ke Sistem';

        if (isLoading) {
            btnLoginSpinner.classList.add('login-spinner--visible');
        } else {
            btnLoginSpinner.classList.remove('login-spinner--visible');
        }
    }

    // ─── Helper: alert banner ─────────────────────────────────────────────────
    function showAlert(type, message) {
        alertMessage.textContent = message;
        alertContainer.classList.remove('login-alert--hidden', 'login-alert--error', 'login-alert--success');

        if (type === 'success') {
            alertContainer.classList.add('login-alert--success');
            alertIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>`;
        } else {
            alertContainer.classList.add('login-alert--error');
            alertIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round"
                d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>`;
        }

        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        alertContainer.classList.add('login-alert--hidden');
        alertContainer.classList.remove('login-alert--error', 'login-alert--success');
    }

    // ─── Helper: field-level error ────────────────────────────────────────────
    function showFieldError(field, message) {
        if (field === 'email') {
            emailError.classList.add('login-field-error--visible');
            emailErrorText.textContent = message;
            emailInput.classList.add('login-input--error');
        } else if (field === 'password') {
            passwordError.classList.add('login-field-error--visible');
            passwordErrorText.textContent = message;
            passwordInput.classList.add('login-input--error');
        }
    }

    function clearFieldError(field) {
        if (field === 'email') {
            emailError.classList.remove('login-field-error--visible');
            emailInput.classList.remove('login-input--error');
        } else if (field === 'password') {
            passwordError.classList.remove('login-field-error--visible');
            passwordInput.classList.remove('login-input--error');
        }
    }

    function clearAllErrors() {
        clearFieldError('email');
        clearFieldError('password');
    }

    // ─── Helper: validasi format email ────────────────────────────────────────
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

});