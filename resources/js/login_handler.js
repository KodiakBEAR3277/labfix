// simple-login.js - Just add this script to your login page

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[name="email"]').value.toLowerCase().trim();
            
            // Simple email to dashboard mapping
            const redirectMap = {
                'admin@labfix.com': '../admin inter/labfix_admin_dashboard.html',
                'it@labfix.com': '../it inter/labfix_it_dashboard.html',
                'user@labfix.com': '../user inter/labfix_user_dashboard.html'
            };
            
            // Check if email exists in our map
            if (redirectMap[email]) {
                // Redirect to the appropriate dashboard
                window.location.href = redirectMap[email];
            } else {
                alert('Email not recognized. Please use: admin@labfix.com, it@labfix.com, or user@labfix.com');
            }

            if (redirectMap[password]) {
                // Redirect to the appropriate dashboard
                window.location.href = redirectMap[password];
            } else {
                alert('Password not recognized. Please use: upassword, ipassword, or apassword');
            }
        });
    }
});