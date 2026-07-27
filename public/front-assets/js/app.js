(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initNavbarScroll();
        initToastr();
    });

    function initNavbarScroll() {
        var navbar = document.querySelector('.smart-navbar');
        if (!navbar) {
            return;
        }

        window.addEventListener('scroll', function () {
            navbar.classList.toggle('scrolled', window.scrollY > 100);
        });
    }

    function initToastr() {
        if (typeof window.toastr === 'undefined') {
            return;
        }

        toastr.options.timeOut = 10000;

        var flash = window.LedrixFlash || {};

        ['success', 'info', 'warning', 'error', 'status'].forEach(function (type) {
            if (flash[type]) {
                toastr[type === 'status' ? 'info' : type](flash[type]);
            }
        });
    }
})();
