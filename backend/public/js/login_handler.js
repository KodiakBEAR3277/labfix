// simple-login.js - Just add this script to your login page

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form');
    if (!loginForm) return;

    // If the form uses POST, let the browser submit to the server
    if ((loginForm.getAttribute('method') || '').toUpperCase() === 'POST') {
        return;
    }

    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const emailInput = this.querySelector('input[name="email"]');
        const pwdInput = this.querySelector('input[name="password"]');

        const email = (emailInput?.value || '').toLowerCase().trim();
        const password = (pwdInput?.value || '').trim();

        // Simple credential map (demo only). All passwords = 'passwords'
        const credentials = {
            'admin@labfix.com': { password: 'passwords', redirect: '/admin/dashboard' },
            'it@labfix.com':    { password: 'passwords', redirect: '/it/dashboard' },
            'user@labfix.com':  { password: 'passwords', redirect: '/dashboard' }
        };

        const entry = credentials[email];
        if (!entry) {
            alert('Email not recognized. Use: admin@labfix.com, it@labfix.com, or user@labfix.com');
            return;
        }

        if (password !== entry.password) {
            alert('Incorrect password. For the demo use: passwords');
            return;
        }

        // Redirect to the appropriate dashboard
        window.location.href = entry.redirect;
    });
});