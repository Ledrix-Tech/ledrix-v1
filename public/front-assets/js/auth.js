(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.querySelector(btn.getAttribute('data-toggle-password'));
                if (!input) {
                    return;
                }

                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';

                var icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bi-eye', !isPassword);
                    icon.classList.toggle('bi-eye-slash', isPassword);
                }
            });
        });

        var workEmail = document.querySelector('#work-email');
        var billingEmail = document.querySelector('#billing-email');
        if (workEmail && billingEmail) {
            workEmail.addEventListener('blur', function () {
                if (!billingEmail.value) {
                    billingEmail.value = workEmail.value;
                }
            });
        }

        var companyName = document.querySelector('#company-name');
        var billingName = document.querySelector('#billing-name');
        if (companyName && billingName) {
            companyName.addEventListener('blur', function () {
                if (!billingName.value) {
                    billingName.value = companyName.value;
                }
            });
        }
    });
})();
